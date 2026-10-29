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
 *  - Recitations already posted on one platform keep today's first slots.
 *  - Al-Kahf recitations are kept for Fridays: the first free slot of each
 *    Friday takes one, mixed so consecutive Fridays vary the qari. When none
 *    is waiting, Friday gets a regular recitation.
 *  - Everything else fills the remaining slots in the order PublishingMixer
 *    gives, which also keeps the qaris around each Friday apart.
 *  - Slots that already went out today are skipped.
 */
class PostingScheduler
{
    public const KAHF = 18;

    public function __construct(private PublishingMixer $mixer) {}

    /**
     * @param  Collection<int, Tilawa>  $waiting  not posted anywhere yet, longest waiting first
     * @param  Collection<int, Tilawa>  $inProgress  posted on some platforms only, first started first
     * @param  Collection<int, Tilawa>  $recentlyPosted  the latest finished posts, oldest first
     * @param  int  $postedToday  recitations that already went out today and left the queue
     * @return Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, kahf: bool, in_progress: bool}>
     */
    public function plan(Collection $waiting, Collection $inProgress, Collection $recentlyPosted, int $postedToday = 0): Collection
    {
        [$kahf, $regular] = $waiting->partition(fn (Tilawa $tilawa) => $this->isKahf($tilawa));

        $todaySlots = $this->slotsOn($this->today());
        $plan = $inProgress->values()->map(fn (Tilawa $tilawa, int $index) => [
            'tilawa' => $tilawa,
            'at' => $todaySlots[min($postedToday + $index, count($todaySlots) - 1)],
            'kahf' => $this->isKahf($tilawa),
            'in_progress' => true,
        ]);

        $slots = $this->bookSlots($kahf->count(), $regular->count(), $postedToday + $inProgress->count());
        $kahfOrder = $this->mixer->mix($kahf->values(), $recentlyPosted->filter(fn (Tilawa $tilawa) => $this->isKahf($tilawa))->values());

        $pinned = [];

        foreach ($slots as $position => $slot) {
            if ($slot['kahf']) {
                $pinned[$position] = $kahfOrder->shift();
            }
        }

        $sequence = $this->mixer->mix($regular->values(), $recentlyPosted->concat($inProgress)->values(), $pinned);

        return $plan->concat($sequence->values()->map(fn (Tilawa $tilawa, int $position) => [
            'tilawa' => $tilawa,
            'at' => $slots[$position]['at'],
            'kahf' => $slots[$position]['kahf'],
            'in_progress' => false,
        ]))->values();
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
     * Walks the calendar from today and books one slot per recitation. The
     * first free slot of a Friday goes to Al-Kahf while one is waiting;
     * once only Al-Kahf recitations remain, the days in between stay empty.
     *
     * @return list<array{at: CarbonImmutable, kahf: bool}>
     */
    private function bookSlots(int $kahfLeft, int $regularLeft, int $usedToday): array
    {
        $slots = [];
        $day = $this->today();
        $skip = $usedToday;

        while ($kahfLeft + $regularLeft > 0) {
            $fridayKahfBooked = false;

            foreach ($this->slotsOn($day) as $at) {
                if ($skip > 0) {
                    $skip--;

                    continue;
                }

                if ($day->isFriday() && ! $fridayKahfBooked && $kahfLeft > 0) {
                    $slots[] = ['at' => $at, 'kahf' => true];
                    $fridayKahfBooked = true;
                    $kahfLeft--;
                } elseif ($regularLeft > 0) {
                    $slots[] = ['at' => $at, 'kahf' => false];
                    $regularLeft--;
                }
            }

            $skip = 0;
            $day = $day->addDay();
        }

        return $slots;
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
