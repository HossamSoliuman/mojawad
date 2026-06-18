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
    | Available background styles. The key is stored on the clip and selects the
    | matching preview partial and ffmpeg background layer.
    |
    */

    'templates' => [
        'dark-waveform',
        'cover-blur',
    ],

    'default_template' => 'dark-waveform',

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | The on-video call-to-action. The string is localized through __(); the
    | UTM params are appended to the tilawa's public URL for attribution.
    |
    */

    'cta_key' => 'Listen to Mojawad on mojawad.org',

    'utm' => [
        'utm_source' => 'tiktok',
        'utm_medium' => 'clip',
        'utm_campaign' => 'short-video',
    ],

];
