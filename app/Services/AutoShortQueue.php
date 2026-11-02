<?php

namespace App\Services;

use App\Jobs\PrepareAutoShort;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use App\Models\VideoClip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class AutoShortQueue
{
    /**
     * Recitations shorter than this cannot hold a full short plus a clean
     * start and end.
     */
    private const MIN_TILAWA_SECONDS = 90;

    /**
     * Queue mosque shorts for a qari, one per recitation, preferring
     * different surahs and recitations that have no mosque short yet. Each
     * short gets its own background so a qari's set does not repeat one.
     *
     * @return Collection<int, VideoClip>
     */
    public function queueForQari(Qari $qari, int $count, User $owner): Collection
    {
        $backgrounds = $this->backgrounds();
        $offset = (int) VideoClip::where('template', 'mosque')->count();

        return $this->pickTilawat($qari, $count)->values()->map(function (Tilawa $tilawa, int $index) use ($owner, $backgrounds, $offset) {
            $clip = VideoClip::create([
                'tilawa_id' => $tilawa->id,
                'created_by' => $owner->id,
                'template' => 'mosque',
                'clip_start' => 0,
                'clip_duration' => 0,
                'background_path' => $backgrounds === [] ? null : $backgrounds[($offset + $index) % count($backgrounds)],
                'status' => 'pending',
            ]);

            PrepareAutoShort::dispatch($clip->id);

            return $clip;
        });
    }

    /**
     * @return Collection<int, Tilawa>
     */
    private function pickTilawat(Qari $qari, int $count): Collection
    {
        $candidates = Tilawa::where('qari_id', $qari->id)
            ->where('status', 'active')
            ->where('duration', '>=', self::MIN_TILAWA_SECONDS)
            ->whereDoesntHave('clips', fn ($query) => $query->where('template', 'mosque'))
            ->orderByDesc('plays_count')
            ->orderBy('id')
            ->get();

        $firstPerSurah = $candidates->unique('surah_number');

        return $firstPerSurah
            ->concat($candidates->diff($firstPerSurah))
            ->take($count);
    }

    /**
     * File names of the mosque backgrounds, in a stable order.
     *
     * @return list<string>
     */
    private function backgrounds(): array
    {
        $directory = config('clips.backgrounds.mosque');

        if (! is_string($directory) || ! is_dir($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(fn ($file) => $file->getFilename())
            ->sort()
            ->values()
            ->all();
    }
}
