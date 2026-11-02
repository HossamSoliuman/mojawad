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
    | Auto shorts transcribe a window of the recitation with faster-whisper,
    | align it to the Quran corpus and cut a clip on recitation pauses. The
    | whisper model is downloaded on first use.
    |
    */

    'transcription' => [
        'python' => env('WHISPER_PYTHON', 'python'),
        'script' => resource_path('scripts/transcribe_words.py'),
        'model' => env('WHISPER_MODEL', 'large-v3-turbo'),
        'timeout' => (int) env('WHISPER_TIMEOUT', 1800),
    ],

    'auto' => [
        'per_qari' => 10,
        'scan_window' => 120,
        'min_duration' => 25,
        'target_duration' => 55,
        'min_match_score' => 0.6,
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
