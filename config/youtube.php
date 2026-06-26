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

    /*
    |--------------------------------------------------------------------------
    | TikTok Cookies File
    |--------------------------------------------------------------------------
    |
    | Path to a Netscape-format cookies.txt file for TikTok authentication.
    | Used to bypass TikTok's 403 blocks on restricted videos. Export from your
    | browser using an extension such as "Get cookies.txt LOCALLY" while logged
    | into TikTok. Public videos download fine without any cookies.
    |
    | Set TIKTOK_COOKIES_BROWSER (e.g. "chrome", "firefox") only if you want
    | yt-dlp to read cookies straight from a browser profile. This is left
    | unset by default because the queue worker often cannot copy a running
    | browser's locked/encrypted cookie database (yt-dlp issue #7271).
    |
    */

    'tiktok_cookies_file' => env('TIKTOK_COOKIES_FILE'),

    'tiktok_cookies_browser' => env('TIKTOK_COOKIES_BROWSER'),

];
