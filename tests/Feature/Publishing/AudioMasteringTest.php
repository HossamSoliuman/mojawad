<?php

use App\Models\Qari;
use App\Models\Tilawa;
use App\Services\AudioMasteringService;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * ffmpeg's stderr as the mastering measurement pass sees it: the build banner
 * and configure flags first, then the input header, then whatever the filters
 * reported. The banner is deliberately long — it is what used to bury the real
 * error inside the 1000-character brand_error column.
 */
function ffmpegStderr(string ...$tail): string
{
    return implode("\n", [
        'ffmpeg version 8.1.1-essentials_build-www.gyan.dev Copyright (c) 2000-2026 the FFmpeg developers',
        '  built with gcc 15.2.0 (Rev13, Built by MSYS2 project)',
        '  configuration: --enable-gpl --enable-version3 --enable-static --disable-w32threads '
            .'--disable-autodetect --enable-cairo --enable-fontconfig --enable-iconv --enable-gnutls '
            .'--enable-libxml2 --enable-gmp --enable-bzlib --enable-lzma --enable-zlib --enable-libsrt '
            .'--enable-libssh --enable-libzmq --enable-avisynth --enable-sdl2 --enable-libwebp '
            .'--enable-libx264 --enable-libx265 --enable-libxvid --enable-libaom --enable-libopenjpeg '
            .'--enable-libvpx --enable-mediafoundation --enable-libass --enable-libfreetype '
            .'--enable-libfribidi --enable-libharfbuzz --enable-libvidstab --enable-libvmaf '
            .'--enable-libzimg --enable-amf --enable-cuda-llvm --enable-cuvid --enable-dxva2 '
            .'--enable-d3d11va --enable-d3d12va --enable-ffnvcodec --enable-libvpl --enable-nvdec '
            .'--enable-nvenc --enable-vaapi --enable-openal --enable-libgme --enable-libopenmpt',
        '  libavutil      60. 26.101 / 60. 26.101',
        '  libavcodec     62. 28.101 / 62. 28.101',
        '  libavformat    62. 12.101 / 62. 12.101',
        '  libavfilter    11. 14.101 / 11. 14.101',
        "Input #0, mp3, from 'recitation.mp3':",
        '  Duration: 00:39:15.67, start: 0.050113, bitrate: 160 kb/s',
        '  Stream #0:0: Audio: mp3 (mp3float), 22050 Hz, stereo, fltp, 160 kb/s',
        ...$tail,
    ]);
}

/**
 * @param  array<string, string>  $overrides
 */
function loudnormJson(array $overrides = []): string
{
    return json_encode(array_merge([
        'input_i' => '-24.74',
        'input_tp' => '-7.02',
        'input_lra' => '15.00',
        'input_thresh' => '-37.35',
        'target_offset' => '0.85',
    ], $overrides));
}

/**
 * @return array{loudness: array<string, string>, start: float, end: float|null, duration: float|null}
 */
function analysis(float $start = 0.0, ?float $end = null, ?float $duration = null, array $loudness = []): array
{
    return ['loudness' => $loudness, 'start' => $start, 'end' => $end, 'duration' => $duration];
}

it('masters without any filter that buffers the whole recitation in memory', function () {
    $filters = implode(',', app(AudioMasteringService::class)
        ->normalizeFilters(analysis(3.4, 2350.5, 2355.67)));

    expect($filters)->not->toContain('areverse')
        ->and($filters)->not->toContain('silenceremove');
});

it('trims the head and tail silence the analysis located', function () {
    $filters = implode(',', app(AudioMasteringService::class)
        ->normalizeFilters(analysis(3.4, 2350.5, 2355.67)));

    expect($filters)->toContain('atrim=start=3.400:end=2350.500')
        ->and($filters)->toContain('asetpts=N/SR/TB');
});

it('places the fade out at the end of the trimmed recitation', function () {
    $filters = implode(',', app(AudioMasteringService::class)
        ->normalizeFilters(analysis(3.4, 2350.5, 2355.67)));

    expect($filters)->toContain('afade=t=in:st=0:d=0.5')
        ->and($filters)->toContain('afade=t=out:st=2345.600:d=1.5');
});

it('skips the fade out when the duration is unknown', function () {
    $filters = implode(',', app(AudioMasteringService::class)->normalizeFilters(analysis()));

    expect($filters)->toContain('afade=t=in')
        ->and($filters)->not->toContain('afade=t=out')
        ->and($filters)->not->toContain('atrim');
});

it('applies the measured loudness as a linear second pass', function () {
    $filters = implode(',', app(AudioMasteringService::class)->normalizeFilters(analysis(
        duration: 600.0,
        loudness: [
            'input_i' => '-24.74',
            'input_tp' => '-7.02',
            'input_lra' => '15.00',
            'input_thresh' => '-37.35',
            'target_offset' => '0.85',
        ],
    )));

    expect($filters)->toContain('loudnorm=I=-14:TP=-1:LRA=11:measured_I=-24.74:measured_TP=-7.02')
        ->and($filters)->toContain(':offset=0.85:linear=true');
});

it('reads loudness, silence bounds and duration from a single ffmpeg pass', function () {
    Process::fake(['*' => Process::result(errorOutput: ffmpegStderr(
        '[silencedetect @ 000001] silence_start: 0',
        '[silencedetect @ 000001] silence_end: 3.5 | silence_duration: 3.5',
        '[silencedetect @ 000001] silence_start: 2350.4',
        '[Parsed_loudnorm_1 @ 000002] ',
        loudnormJson(),
    ))]);

    $analysis = app(AudioMasteringService::class)->analyze('recitation.mp3');

    expect($analysis['start'])->toBe(3.4)
        ->and($analysis['end'])->toBe(2350.5)
        ->and($analysis['duration'])->toBe(2355.67)
        ->and($analysis['loudness']['input_i'])->toBe('-24.74');

    Process::assertRan(fn ($process): bool => str_contains(implode(' ', $process->command), 'silencedetect')
        && str_contains(implode(' ', $process->command), 'print_format=json'));
});

it('keeps the pauses between ayat in the middle of a recitation', function () {
    Process::fake(['*' => Process::result(errorOutput: ffmpegStderr(
        '[silencedetect @ 000001] silence_start: 412.8',
        '[silencedetect @ 000001] silence_end: 415.1 | silence_duration: 2.3',
        '[silencedetect @ 000001] silence_start: 980.4',
        '[silencedetect @ 000001] silence_end: 982.0 | silence_duration: 1.6',
        loudnormJson(),
    ))]);

    $service = app(AudioMasteringService::class);
    $analysis = $service->analyze('recitation.mp3');

    expect($analysis['start'])->toBe(0.0)
        ->and($analysis['end'])->toBeNull()
        ->and(implode(',', $service->normalizeFilters($analysis)))->not->toContain('atrim');
});

it('reports the tail of ffmpeg stderr so the build banner does not bury the error', function () {
    $tilawa = Tilawa::factory()->create(['qari_id' => Qari::factory()->create()->id]);
    Storage::disk('public')->put($tilawa->audio_path, 'audio');

    Process::fake(['*' => Process::result(
        errorOutput: ffmpegStderr(
            loudnormJson(),
            '[af#0:0 @ 000003] Task finished with error code: -12 (Cannot allocate memory)',
            'Conversion failed!',
        ),
        exitCode: 1,
    )]);

    try {
        app(AudioMasteringService::class)->master($tilawa->fresh());
        $this->fail('Mastering should have thrown when ffmpeg exits non-zero.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('Conversion failed!')
            ->and($e->getMessage())->not->toContain('--enable-libx264')
            ->and(strlen($e->getMessage()))->toBeLessThan(1000);
    }
});
