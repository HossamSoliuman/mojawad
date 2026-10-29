<?php

namespace App\Services;

use App\Models\Tilawa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Books the publishing queue into posting slots, from today onwards.
 *
 * The plan is worked out from the queue every time rather than stored, so it
 * re-plans the moment a recitation arrives from Preparation or goes live:
 *  - Recitations already posted or scheduled on the platforms hold the slot
 *    nearest the time they go live, so nothing else is booked over them. A
 *    half-posted one that first went out before today catches up today.
 *  - Al-Kahf recitations are kept for Fridays: the first free slot of each
 *    Friday takes one, mixed so consecutive Fridays vary the qari, unless an
 *    Al-Kahf post is already booked that Friday. When none is waiting,
 *    Friday gets a regular recitation.
 *  - Everything else fills the remaining slots in the order PublishingMixer
 *    gives, which also keeps the qaris around each booked post apart.
 */
class PostingScheduler
{
    public const KAHF = 18;

    public function __construct(private PublishingMixer $mixer) {}

    /**
     * @param  Collection<int, Tilawa>  $waiting  not posted anywhere yet, longest waiting first
     * @param  Collection<int, array{tilawa: Tilawa, at: ?CarbonImmutable, posted: bool}>  $booked  posted or scheduled on some platforms and still queued, or on all of them (posted), with when they go live
     * @param  Collection<int, Tilawa>  $recentlyPosted  the latest posts that went live before today, oldest first
     * @return Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, kahf: bool, in_progress: bool, posted: bool}>
     */
    public function plan(Collection $waiting, Collection $booked, Collection $recentlyPosted): Collection
    {
        [$kahf, $regular] = $waiting->partition(fn (Tilawa $tilawa) => $this->isKahf($tilawa));

        $booked = $this->placeBooked($booked);
        $slots = $this->bookSlots($kahf->count(), $regular->count(), $booked);
        $kahfHistory = $recentlyPosted->concat($booked->pluck('tilawa'))->filter(fn (Tilawa $tilawa) => $this->isKahf($tilawa));
        $kahfOrder = $this->mixer->mix($kahf->values(), $kahfHistory->values());

        $pinned = [];

        foreach ($slots as $position => $slot) {
            if ($slot['booked'] !== null) {
                $pinned[$position] = $slot['booked']['tilawa'];
            } elseif ($slot['kahf']) {
                $pinned[$position] = $kahfOrder->shift();
            }
        }

        $sequence = $this->mixer->mix($regular->values(), $recentlyPosted->values(), $pinned);

        return $sequence->values()->map(fn (Tilawa $tilawa, int $position) => [
            'tilawa' => $tilawa,
            'at' => $slots[$position]['at'],
            'kahf' => $slots[$position]['kahf'],
            'in_progress' => $slots[$position]['booked'] !== null && ! $slots[$position]['booked']['posted'],
            'posted' => $slots[$position]['booked']['posted'] ?? false,
        ]);
    }

    /**
     * The coming days, starting today, with what is booked on each.
     *
     * @param  Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, kahf: bool, in_progress: bool}>  $plan
     * @return list<array{date: CarbonImmutable, posts: list<array{tilawa: Tilawa, at: CarbonImmutable, kahf: bool, in_progress: bool}>}>
     */
    public function days(Collection $plan, int $count = 7): array
    {
        $today = $this->today();

        return collect(range(0, $count - 1))
            ->map(function (int $offset) use ($today, $plan): array {
                $date = $today->addDays($offset);

                return [
                    'date' => $date,
                    'posts' => $plan->filter(fn (array $entry) => $entry['at']->isSameDay($date))->values()->all(),
                ];
            })
            ->all();
    }

    /**
     * "Today", "Tomorrow", or the weekday name.
     */
    public function dayLabel(CarbonImmutable $date): string
    {
        $today = $this->today();

        return match (true) {
            $date->isSameDay($today) => __('Today'),
            $date->isSameDay($today->addDay()) => __('Tomorrow'),
            default => $date->locale(app()->getLocale())->translatedFormat('l'),
        };
    }

    public function isKahf(Tilawa $tilawa): bool
    {
        $surahs = array_map('intval', (array) ($tilawa->surahs ?? []));

        return (int) $tilawa->surah_number === self::KAHF || in_array(self::KAHF, $surahs, true);
    }

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('publishing.schedule.timezone'))->startOfDay();
    }

    /**
     * Gives each booked post its local time, soonest first. Posts with no
     * time, or one before today, take today's slots in the order given.
     *
     * @param  Collection<int, array{tilawa: Tilawa, at: ?CarbonImmutable, posted: bool}>  $booked
     * @return Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, posted: bool}>
     */
    private function placeBooked(Collection $booked): Collection
    {
        $today = $this->today();
        $todaySlots = $this->slotsOn($today);
        $catchingUp = 0;

        return $booked
            ->map(function (array $entry) use ($today, $todaySlots, &$catchingUp): array {
                $at = $entry['at']?->setTimezone($today->getTimezone());

                if ($at === null || $at->lt($today)) {
                    $at = $todaySlots[min($catchingUp++, count($todaySlots) - 1)];
                }

                return [...$entry, 'at' => $at];
            })
            ->sortBy(fn (array $entry) => $entry['at']->getTimestamp())
            ->values();
    }

    /**
     * Walks the calendar from today, laying out the booked posts on their
     * days and one free slot per waiting recitation around them. Each booked
     * post takes the slot nearest its time. The first free slot of a Friday
     * goes to Al-Kahf while one is waiting, unless a booked Al-Kahf post
     * already covers that Friday. Once only Al-Kahf recitations remain, the
     * days in between stay empty.
     *
     * @param  Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, posted: bool}>  $booked  soonest first
     * @return list<array{at: CarbonImmutable, kahf: bool, booked: ?array{tilawa: Tilawa, at: CarbonImmutable, posted: bool}}>
     */
    private function bookSlots(int $kahfLeft, int $regularLeft, Collection $booked): array
    {
        $slots = [];
        $day = $this->today();
        $lastBooked = $booked->last()['at'] ?? null;

        while ($kahfLeft + $regularLeft > 0 || ($lastBooked !== null && $day->lte($lastBooked))) {
            $free = $this->slotsOn($day);
            $daySlots = [];
            $kahfSlotOpen = $day->isFriday();

            foreach ($booked->filter(fn (array $entry) => $entry['at']->isSameDay($day)) as $entry) {
                $isKahf = $this->isKahf($entry['tilawa']);
                $daySlots[] = ['at' => $entry['at'], 'kahf' => $isKahf, 'booked' => $entry];
                $kahfSlotOpen = $kahfSlotOpen && ! $isKahf;

                if ($free !== []) {
                    unset($free[$this->nearestSlot($free, $entry['at'])]);
                }
            }

            foreach ($free as $at) {
                if ($kahfSlotOpen && $kahfLeft > 0) {
                    $daySlots[] = ['at' => $at, 'kahf' => true, 'booked' => null];
                    $kahfSlotOpen = false;
                    $kahfLeft--;
                } elseif ($regularLeft > 0) {
                    $daySlots[] = ['at' => $at, 'kahf' => false, 'booked' => null];
                    $regularLeft--;
                }
            }

            usort($daySlots, fn (array $a, array $b) => $a['at']->getTimestamp() <=> $b['at']->getTimestamp());
            array_push($slots, ...$daySlots);
            $day = $day->addDay();
        }

        return $slots;
    }

    /**
     * @param  array<int, CarbonImmutable>  $slots
     */
    private function nearestSlot(array $slots, CarbonImmutable $at): int
    {
        return collect($slots)
            ->sortBy(fn (CarbonImmutable $slot) => abs($slot->getTimestamp() - $at->getTimestamp()))
            ->keys()
            ->first();
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function slotsOn(CarbonImmutable $day): array
    {
        $times = config($day->isFriday() ? 'publishing.schedule.friday' : 'publishing.schedule.daily');

        return array_map(fn (string $time) => $day->setTimeFromTimeString($time), $times);
    }
}
