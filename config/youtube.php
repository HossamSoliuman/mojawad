<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External Binaries
    |--------------------------------------------------------------------------
    |
    | Paths to the yt-dlp and ffmpeg binaries used by the Studio importer to
    | probe and extract audio from YouTube. On a host where they are on the
    | system PATH the bare command names are enough; otherwise provide an
    | absolute path via the environment.
    |
    */

    'ytdlp_path' => env('YTDLP_PATH', 'yt-dlp'),

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    /*
    |--------------------------------------------------------------------------
    | Process Timeouts (seconds)
    |--------------------------------------------------------------------------
    */

    'probe_timeout' => (int) env('YOUTUBE_PROBE_TIMEOUT', 60),

    'download_timeout' => (int) env('YOUTUBE_DOWNLOAD_TIMEOUT', 1800),

];
