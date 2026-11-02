<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Rendered clips and their derived assets live on the "public" disk under
    | these directories. Like the Studio importer this is not S3-aware; revisit
    | if remote storage is ever required.
    |
    */

    'disk' => 'public',

    'output_dir' => 'clips',

    'poster_dir' => 'clips/posters',

    'overlay_dir' => 'clips/overlays',

    /*
    |--------------------------------------------------------------------------
    | Clip Length
    |--------------------------------------------------------------------------
    |
    | Presets offered in the editor (seconds) and the hard cap enforced both in
    | validation and when building the ffmpeg command.
    |
    */

    'presets' => [15, 30, 60],

    'max_duration' => 60,

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    'width' => 1080,

    'height' => 1920,

    'fps' => 30,

    'render_timeout' => (int) env('CLIP_RENDER_TIMEOUT', 600),

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | "cover-blur" uses the tilawa's cover image behind the editor's overlay.
    | "mosque" is the auto-short look: a slowly zooming mosque photo with the
    | recited ayat burned in as subtitles.
    |
    */

    'templates' => [
        'cover-blur',
        'mosque',
    ],

    'default_template' => 'cover-blur',

    /*
    |--------------------------------------------------------------------------
    | Backgrounds & Fonts
    |--------------------------------------------------------------------------
    |
    | Photo backgrounds per template, pre-cropped to 9:16 with a little room
    | for the slow zoom. The bundled photos are CC0 (Wikimedia Commons). The
    | fonts directory is handed to libass for the burned-in Arabic text.
    |
    */

    'backgrounds' => [
        'mosque' => resource_path('clips/backgrounds/mosque'),
    ],

    'fonts_dir' => resource_path('fonts'),

    'zoom' => 1.08,

    /*
    |--------------------------------------------------------------------------
    | Auto Shorts
    |--------------------------------------------------------------------------
    |
    | Auto shorts send a window of the recitation to Gemini (the key from
    | config/publishing.php) for timed phrases, snap them to detected pauses,
    | align them to the Quran corpus and cut the clip on those pauses. Busy
    | models are retried, then the next model in the list is used.
    |
    */

    'transcription' => [
        'models' => explode(',', (string) env('CLIP_ASR_MODELS', 'gemini-3.6-flash,gemini-3.5-flash,gemini-flash-latest,gemini-flash-lite-latest')),
        'attempts' => 3,
        'timeout' => (int) env('CLIP_ASR_TIMEOUT', 300),
        'silence_db' => -32,
    ],

    'auto' => [
        'per_qari' => 10,
        'scan_window' => 150,
        'min_duration' => 25,
        'target_duration' => 55,
        'min_match_score' => 0.72,
        'max_cue_words' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | The on-video call-to-action. The string is localized through __(); the
    | UTM params are appended to the tilawa's public URL for attribution.
    |
    */

    'cta_key' => 'Listen to Mojawad on mojawad.com',

    'utm' => [
        'utm_source' => 'tiktok',
        'utm_medium' => 'clip',
        'utm_campaign' => 'short-video',
    ],

];
