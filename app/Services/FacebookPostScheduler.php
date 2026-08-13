<?php

namespace App\Services;

use App\Models\FacebookPost;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Turns the cadence in `config('publishing.facebook.cadence')` into concrete
 * publishing times. Approval calls {@see nextSlot()} instead of asking an editor
 * for a date, so the queue stays evenly spaced without anyone maintaining it.
 */
class FacebookPostScheduler
{
    /**
     * The first slot that is both far enough away to reach the queue and not
     * already booked by another scheduled post.
     */
    public function nextSlot(?CarbonImmutable $after = null): CarbonImmutable
    {
        $earliest = ($after ?? CarbonImmutable::now())->addMinutes($this->leadMinutes());
        $booked = $this->bookedSlots($earliest);
        $day = $earliest->setTimezone($this->timezone())->startOfDay();

        for ($offset = 0; $offset <= $this->horizonDays(); $offset++) {
            foreach ($this->slotsOn($day->addDays($offset)) as $slot) {
                if ($slot->greaterThanOrEqualTo($earliest) && ! in_array($slot->getTimestamp(), $booked, true)) {
                    return $slot->setTimezone('UTC');
                }
            }
        }

        throw new RuntimeException('Every publishing slot is taken for the next '.$this->horizonDays().' days.');
    }

    /**
     * The cadence as readable lines, so the workspace can state the rhythm it is
     * booking into rather than leaving editors to infer it from dates.
     *
     * @return array{timezone: string, weekly: int, daily: list<string>, friday: list<string>}
     */
    public function summary(): array
    {
        $daily = $this->times('daily');
        $friday = $this->times('friday');

        return [
            'timezone' => $this->timezone(),
            'weekly' => (count($daily) * 6) + count($friday),
            'daily' => $daily,
            'friday' => $friday,
        ];
    }

    /**
     * Every slot on one day, in the cadence timezone and in order.
     *
     * @return list<CarbonImmutable>
     */
    private function slotsOn(CarbonImmutable $day): array
    {
        $times = $day->isFriday() ? $this->times('friday') : $this->times('daily');

        $slots = array_map(
            fn (string $time): CarbonImmutable => $day->setTimeFromTimeString($time),
            $times,
        );

        usort($slots, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        return $slots;
    }

    /**
     * Timestamps already promised to a scheduled post. Published posts are
     * ignored: their slot is spent, and reusing the same clock time on a later
     * day is exactly what the cadence intends.
     *
     * @return list<int>
     */
    private function bookedSlots(CarbonImmutable $from): array
    {
        return FacebookPost::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '>=', $from)
            ->pluck('scheduled_for')
            ->map(fn ($slot): int => $slot->getTimestamp())
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function times(string $key): array
    {
        $times = (array) config("publishing.facebook.cadence.{$key}", []);

        return array_values(array_filter(array_map('strval', $times)));
    }

    private function timezone(): string
    {
        return (string) config('publishing.facebook.cadence.timezone', 'UTC');
    }

    private function leadMinutes(): int
    {
        return (int) config('publishing.facebook.cadence.lead_minutes', 15);
    }

    private function horizonDays(): int
    {
        return (int) config('publishing.facebook.cadence.horizon_days', 180);
    }
}
