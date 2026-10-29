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
 *  - A qari or surah featured only a few slots ago is pushed back.
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
     * @return Collection<int, Tilawa>
     */
    public function mix(Collection $queue, Collection $recentlyPosted = new Collection): Collection
    {
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

        $caps = $this->runCaps($sizes, $total, $previousQari, $run);
        $mixed = [];

        for ($slot = 0; $slot < $total; $slot++) {
            $choice = null;

            foreach ($this->candidates(array_map('count', $pending), $total - $slot, $previousQari, $run, $caps) as $qari) {
                $index = $this->nextIndex($pending[$qari], $lastSurahSlot, $slot);

                $score = ($placed[$qari] + $phases[$qari]) * $total / $sizes[$qari] - $slot;

                if (isset($lastQariSlot[$qari]) && $caps[$qari] === 1) {
                    $score += self::QARI_PENALTY * max(0, $qariSpacing - ($slot - $lastQariSlot[$qari]) + 1);
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

            $run = $qari === $previousQari ? $run + 1 : 1;
            $previousQari = $qari;
            $placed[$qari]++;
            $lastQariSlot[$qari] = $slot;
            $this->rememberSurah($lastSurahSlot, $tilawa, $slot);
            $mixed[] = $tilawa;
        }

        return collect($mixed);
    }

    /**
     * The longest run each qari is allowed: the shortest one that still fits
     * all of its recitations between everyone else's, counting the run the
     * latest posts already started.
     *
     * @param  array<int, int>  $sizes
     * @return array<int, int>
     */
    private function runCaps(array $sizes, int $total, ?int $previousQari, int $run): array
    {
        $caps = [];

        foreach ($sizes as $qari => $size) {
            $started = $qari === $previousQari ? $run : 0;
            $cap = 1;

            while (! $this->fitsRunCap($size, $total, $cap, $started)) {
                $cap++;
            }

            $caps[$qari] = $cap;
        }

        return $caps;
    }

    /**
     * The qaris allowed to take the next slot: those that stay within their
     * run cap and leave every qari able to stay within its own.
     *
     * @param  array<int, int>  $remaining
     * @param  array<int, int>  $caps
     * @return list<int>
     */
    private function candidates(array $remaining, int $left, ?int $previousQari, int $run, array $caps): array
    {
        $qaris = array_keys($remaining);

        $allowed = array_values(array_filter($qaris, function (int $qari) use ($remaining, $left, $previousQari, $run, $caps): bool {
            $runAfterPick = $qari === $previousQari ? $run + 1 : 1;
            $afterPick = $remaining;
            $afterPick[$qari]--;

            return $runAfterPick <= $caps[$qari]
                && $this->fitsRunCaps($afterPick, $left - 1, $qari, $runAfterPick, $caps);
        }));

        return $allowed ?: $qaris;
    }

    /**
     * Whether the remaining recitations fit into the slots left with every
     * qari's runs within its cap.
     *
     * @param  array<int, int>  $remaining
     * @param  array<int, int>  $caps
     */
    private function fitsRunCaps(array $remaining, int $left, int $previousQari, int $run, array $caps): bool
    {
        foreach ($remaining as $qari => $count) {
            if (! $this->fitsRunCap($count, $left, $caps[$qari], $qari === $previousQari ? $run : 0)) {
                return false;
            }
        }

        return true;
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
