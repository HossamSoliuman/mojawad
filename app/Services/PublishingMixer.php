<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Support\Collection;

/**
 * Orders the publishing queue so consecutive posts feel varied rather than
 * following the order the videos were prepared in.
 *
 * Each slot is filled greedily:
 *  - Every qari gets a run cap: 1 whenever the queue can avoid the same qari
 *    twice in a row, otherwise the shortest run its share of the queue
 *    allows. Only picks that keep every cap reachable are considered, so a
 *    qari holding most of the queue is spread out instead of piling up at
 *    the bottom.
 *  - Each qari has an ideal next slot, spacing its recitations evenly across
 *    the whole queue in proportion to how many it has. Qaris of the same
 *    size get staggered start phases, so qaris with a single recitation
 *    spread out instead of meeting in the middle.
 *  - A qari or surah featured only a few slots ago, or a qari pinned to one
 *    of the next few slots, is pushed back.
 *  - The mix carries on from the latest posts, so the queue never opens with
 *    the qari the channel just featured.
 *
 * Ties go to the longest-waiting recitation, and the result is deterministic,
 * so the list does not reshuffle between renders while the queue is unchanged.
 */
class PublishingMixer
{
    /** How many of the latest posts the mix carries on from. */
    public const RECENT_POSTS = 4;

    /** Slots a qari ideally stays away after being featured. */
    private const QARI_SPACING = 4;

    /** Slots a surah ideally stays away after being featured. */
    private const SURAH_SPACING = 3;

    /** Slots of delay added for each slot a qari comes back too early. */
    private const QARI_PENALTY = 1.5;

    /** Slots of delay added for each slot a surah comes back too early. */
    private const SURAH_PENALTY = 0.5;

    /**
     * @param  Collection<int, Tilawa>  $queue  recitations waiting to be posted, longest waiting first
     * @param  Collection<int, Tilawa>  $recentlyPosted  the latest posts, oldest first
     * @param  array<int, Tilawa>  $pinned  recitations that must land at a set position of the result, keyed by it
     * @return Collection<int, Tilawa>
     */
    public function mix(Collection $queue, Collection $recentlyPosted = new Collection, array $pinned = []): Collection
    {
        ksort($pinned);

        /** @var array<int, list<Tilawa>> $pending */
        $pending = [];

        foreach ($queue as $tilawa) {
            $pending[$this->qariOf($tilawa)][] = $tilawa;
        }

        $total = $queue->count();
        $sizes = array_map('count', $pending);
        $phases = $this->phases($sizes);
        $placed = array_fill_keys(array_keys($pending), 0);
        $qariSpacing = min(count($pending) - 1, self::QARI_SPACING);

        /** @var array<int, int> $lastQariSlot */
        $lastQariSlot = [];

        /** @var array<int, int> $lastSurahSlot */
        $lastSurahSlot = [];

        $previousQari = null;
        $run = 0;
        $slot = -$recentlyPosted->count();

        foreach ($recentlyPosted as $tilawa) {
            $qari = $this->qariOf($tilawa);
            $run = $qari === $previousQari ? $run + 1 : 1;
            $previousQari = $qari;
            $lastQariSlot[$qari] = $slot;
            $this->rememberSurah($lastSurahSlot, $tilawa, $slot);
            $slot++;
        }

        $pinnedQaris = array_map(fn (Tilawa $tilawa) => $this->qariOf($tilawa), $pinned);
        $length = $total + count($pinned);
        $caps = $this->runCaps($sizes, $length, $previousQari, $run, $pinnedQaris);
        $mixed = [];
        $picked = 0;

        for ($slot = 0; $picked < $total || $pinned !== []; $slot++) {
            $pinnedSlot = isset($pinned[$slot]) ? $slot : ($picked === $total ? array_key_first($pinned) : null);

            if ($pinnedSlot !== null) {
                $tilawa = $pinned[$pinnedSlot];
                $qari = $this->qariOf($tilawa);
                unset($pinned[$pinnedSlot]);
            } else {
                $choice = null;
                $upcoming = $this->upcomingPinnedSlots($pinned, $slot);
                $candidates = $this->candidates(array_map('count', $pending), $slot, $length, $previousQari, $run, $caps, $pinnedQaris);

                foreach ($candidates as $qari) {
                    $index = $this->nextIndex($pending[$qari], $lastSurahSlot, $slot);

                    $score = ($placed[$qari] + $phases[$qari]) * $total / $sizes[$qari] - $picked;

                    $nearest = min(
                        isset($lastQariSlot[$qari]) ? $slot - $lastQariSlot[$qari] : PHP_INT_MAX,
                        isset($upcoming[$qari]) ? $upcoming[$qari] - $slot : PHP_INT_MAX,
                    );

                    if ($nearest !== PHP_INT_MAX && $caps[$qari] === 1) {
                        $score += self::QARI_PENALTY * max(0, $qariSpacing - $nearest + 1);
                    }

                    $surah = $this->surahOf($pending[$qari][$index]);

                    if ($surah !== null && isset($lastSurahSlot[$surah])) {
                        $score += self::SURAH_PENALTY * max(0, self::SURAH_SPACING - ($slot - $lastSurahSlot[$surah]) + 1);
                    }

                    if ($choice === null || $score < $choice['score']) {
                        $choice = ['qari' => $qari, 'index' => $index, 'score' => $score];
                    }
                }

                $qari = $choice['qari'];
                $tilawa = array_splice($pending[$qari], $choice['index'], 1)[0];

                if ($pending[$qari] === []) {
                    unset($pending[$qari]);
                }

                $placed[$qari]++;
                $picked++;
            }

            $run = $qari === $previousQari ? $run + 1 : 1;
            $previousQari = $qari;
            $lastQariSlot[$qari] = $slot;
            $this->rememberSurah($lastSurahSlot, $tilawa, $slot);
            $mixed[] = $tilawa;
        }

        return collect($mixed);
    }

    /**
     * The next slot each qari is pinned to after the current one, so a qari
     * is also kept away from where it is about to appear.
     *
     * @param  array<int, Tilawa>  $pinned
     * @return array<int, int>
     */
    private function upcomingPinnedSlots(array $pinned, int $slot): array
    {
        $upcoming = [];

        foreach ($pinned as $pinnedSlot => $tilawa) {
            if ($pinnedSlot > $slot) {
                $upcoming[$this->qariOf($tilawa)] ??= $pinnedSlot;
            }
        }

        return $upcoming;
    }

    /**
     * The longest run each qari is allowed: 1 when its recitations can all be
     * kept apart, otherwise the shortest run that still fits them between
     * everyone else's, counting the run the latest posts already started.
     *
     * @param  array<int, int>  $sizes
     * @param  array<int, int>  $pinnedQaris
     * @return array<int, int>
     */
    private function runCaps(array $sizes, int $length, ?int $previousQari, int $run, array $pinnedQaris): array
    {
        $caps = [];
        $free = array_sum($sizes);

        foreach ($sizes as $qari => $size) {
            if ($size <= $this->spacedCapacity($qari, 0, $length, $previousQari, $pinnedQaris)) {
                $caps[$qari] = 1;

                continue;
            }

            $started = $qari === $previousQari ? $run : 0;
            $cap = 2;

            while (! $this->fitsRunCap($size, $free, $cap, $started)) {
                $cap++;
            }

            $caps[$qari] = $cap;
        }

        return $caps;
    }

    /**
     * The qaris allowed to take the next slot: those that stay within their
     * run cap, do not touch a slot they are pinned to, and leave every qari
     * able to stay within its own cap.
     *
     * @param  array<int, int>  $remaining
     * @param  array<int, int>  $caps
     * @param  array<int, int>  $pinnedQaris
     * @return list<int>
     */
    private function candidates(array $remaining, int $slot, int $length, ?int $previousQari, int $run, array $caps, array $pinnedQaris): array
    {
        $qaris = array_keys($remaining);

        $allowed = array_values(array_filter($qaris, function (int $qari) use ($remaining, $slot, $length, $previousQari, $run, $caps, $pinnedQaris): bool {
            $runAfterPick = $qari === $previousQari ? $run + 1 : 1;

            if ($runAfterPick > $caps[$qari] || ($caps[$qari] === 1 && ($pinnedQaris[$slot + 1] ?? null) === $qari)) {
                return false;
            }

            $afterPick = $remaining;
            $afterPick[$qari]--;

            return $this->fitsRunCaps($afterPick, $slot + 1, $length, $qari, $runAfterPick, $caps, $pinnedQaris);
        }));

        return $allowed ?: $qaris;
    }

    /**
     * Whether the remaining recitations fit into the free slots from `$from`
     * onwards with every qari's runs within its cap.
     *
     * @param  array<int, int>  $remaining
     * @param  array<int, int>  $caps
     * @param  array<int, int>  $pinnedQaris
     */
    private function fitsRunCaps(array $remaining, int $from, int $length, int $previousQari, int $run, array $caps, array $pinnedQaris): bool
    {
        $free = array_sum($remaining);

        if ($free > 0 && ! $this->nextSlotFillable($remaining, $from, $previousQari, $caps, $pinnedQaris)) {
            return false;
        }

        foreach ($remaining as $qari => $count) {
            $fits = $caps[$qari] === 1
                ? $count <= $this->spacedCapacity($qari, $from, $length, $previousQari, $pinnedQaris)
                : $this->fitsRunCap($count, $free, $caps[$qari], $qari === $previousQari ? $run : 0);

            if (! $fits) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether some qari can still take the next free slot, which squeezed
     * between a qari and a slot pinned to another can leave nobody to fill it.
     *
     * @param  array<int, int>  $remaining
     * @param  array<int, int>  $caps
     * @param  array<int, int>  $pinnedQaris
     */
    private function nextSlotFillable(array $remaining, int $from, int $previousQari, array $caps, array $pinnedQaris): bool
    {
        $slot = $from;

        while (isset($pinnedQaris[$slot])) {
            $slot++;
        }

        $before = $slot === $from ? $previousQari : $pinnedQaris[$slot - 1];
        $after = $pinnedQaris[$slot + 1] ?? null;

        foreach ($remaining as $qari => $count) {
            if ($count > 0 && ($caps[$qari] > 1 || ($qari !== $before && $qari !== $after))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The most recitations a qari can still take from `$from` onwards without
     * ever touching itself. The free slots it may use (not next to a slot it
     * is pinned to, nor right after itself) form runs of consecutive slots,
     * and each run fits one recitation in every other slot.
     *
     * @param  array<int, int>  $pinnedQaris
     */
    private function spacedCapacity(int $qari, int $from, int $length, ?int $previousQari, array $pinnedQaris): int
    {
        $capacity = 0;
        $block = 0;

        for ($slot = $from; $slot < $length; $slot++) {
            $before = $slot === $from ? $previousQari : ($pinnedQaris[$slot - 1] ?? null);
            $usable = ! isset($pinnedQaris[$slot]) && $before !== $qari && ($pinnedQaris[$slot + 1] ?? null) !== $qari;

            if ($usable) {
                $block++;

                continue;
            }

            $capacity += intdiv($block + 1, 2);
            $block = 0;
        }

        return $capacity + intdiv($block + 1, 2);
    }

    /**
     * A qari's recitations sit in one run after each of everyone else's
     * recitations, plus an optional run right away that continues the one the
     * previous slots already started.
     */
    private function fitsRunCap(int $count, int $left, int $cap, int $started): bool
    {
        return $count <= $cap * ($left - $count) + max(0, $cap - $started);
    }

    /**
     * Staggers where each qari's run starts. Qaris with the same number of
     * recitations share the stride, so they split it evenly between them,
     * in the order they first appear in the queue.
     *
     * @param  array<int, int>  $sizes
     * @return array<int, float>
     */
    private function phases(array $sizes): array
    {
        $phases = [];

        foreach (collect($sizes)->groupBy(fn (int $size) => $size, preserveKeys: true) as $sameSize) {
            foreach ($sameSize->keys()->values() as $rank => $qari) {
                $phases[$qari] = ($rank + 1) / ($sameSize->count() + 1);
            }
        }

        return $phases;
    }

    /**
     * The qari's longest-waiting recitation whose surah was not featured
     * recently, falling back to its longest-waiting one.
     *
     * @param  list<Tilawa>  $recitations
     * @param  array<int, int>  $lastSurahSlot
     */
    private function nextIndex(array $recitations, array $lastSurahSlot, int $slot): int
    {
        foreach ($recitations as $index => $tilawa) {
            $surah = $this->surahOf($tilawa);

            if ($surah === null || ! isset($lastSurahSlot[$surah]) || $slot - $lastSurahSlot[$surah] > self::SURAH_SPACING) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, int>  $lastSurahSlot
     */
    private function rememberSurah(array &$lastSurahSlot, Tilawa $tilawa, int $slot): void
    {
        $surah = $this->surahOf($tilawa);

        if ($surah !== null) {
            $lastSurahSlot[$surah] = $slot;
        }
    }

    private function qariOf(Tilawa $tilawa): int
    {
        return (int) $tilawa->qari_id;
    }

    private function surahOf(Tilawa $tilawa): ?int
    {
        $surah = $tilawa->surah_number ?? ($tilawa->surahs[0] ?? null);

        return $surah === null ? null : (int) $surah;
    }
}
