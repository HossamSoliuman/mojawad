<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Like the Studio importer and clip renderer, the pipeline is not S3-aware.
    | Everything lives on the "public" disk (rooted at public/uploads), so the
    | cleaned audio in `clean_dir` is served by Tilawa::audio_src unchanged and
    | derived assets resolve through Storage::disk('public')->url().
    |
    */

    'disk' => 'public',

    // Local recitation library scanned by the Factory folder importer. Each
    // top-level directory is a reciter's folder of raw audio files that are
    // ingested in place (read straight from this disk, never copied).
    'library_disk' => 'recitations',

    // Ingest
    'source_dir' => 'sources',            // sources/{qari_id}/original.mp3 (raw, disposable input)
    'clean_dir' => 'tilawat',             // cleaned audio lands beside normal tilawa audio
    'clean_bitrate' => '192k',

    // Derived asset directories (mirror config/clips.php layout)
    'master_dir' => 'published/audio',
    'cover_dir' => 'published/covers',
    'video_dir' => 'published/videos',
    'poster_dir' => 'published/posters',

    // ── AI ayah detection ──────────────────────────────────────────────
    'transcription' => [
        'driver' => env('ASR_DRIVER', 'openai'),   // openai | local
        'api_key' => env('ASR_API_KEY'),
        'base_url' => env('ASR_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('ASR_MODEL', 'whisper-1'),
        'timeout' => (int) env('ASR_TIMEOUT', 600),
        'window' => (int) env('ASR_WINDOW', 30),   // seconds of head/tail audio transcribed
    ],
    'quran_text_path' => resource_path('data/quran-uthmani.json'), // fixed reference corpus
    'ayah_match_min_score' => 0.7,        // below → flag ayah_confidence = 'low'
    'title_pattern' => 'ما تيسر من سوره :surah من الايه :from الي الايه :to',
    'title_pattern_surah_only' => 'ما تيسر من سوره :surah',
    'title_pattern_surahs_dual' => 'ما تيسر من سورتي :surahs',   // exactly two surahs
    'title_pattern_surahs' => 'ما تيسر من سور :surahs',          // three or more
    'title_part_suffix' => ' (الجزء :part)',                     // one file of a multi-part surah

    // ── Subtitle alignment ─────────────────────────────────────────────
    'subtitles' => [
        'enabled' => (bool) env('SUBTITLES_ENABLED', false), // needs aeneas on the worker host
        'aligner' => env('SUBTITLE_ALIGNER', 'aeneas'),   // aeneas | whisperx (word-level)
        'binary' => env('AENEAS_BINARY', 'python'),       // invoked as: python -m aeneas.tools.execute_task
        'granularity' => env('SUBTITLE_GRANULARITY', 'ayah'), // ayah | word
        'format' => 'vtt',                                 // vtt for web + YouTube; srt also fine
        'dir' => 'published/subtitles',
        'burn_style' => 'FontName=Amiri,FontSize=42,PrimaryColour=&Hffffff&,Outline=2',
        'timeout' => (int) env('SUBTITLE_TIMEOUT', 900),
    ],

    // ── Audio mastering ────────────────────────────────────────────────
    'loudness' => ['i' => -14.0, 'tp' => -1.0, 'lra' => 11.0],
    'silence_trim' => true,
    'fade' => ['in' => 0.5, 'out' => 1.5],
    'intro_audio' => env('BRAND_INTRO_AUDIO'),
    'outro_audio' => env('BRAND_OUTRO_AUDIO'),
    'mp3_bitrate' => '320k',
    'id3' => ['album_artist' => 'Mojawad', 'genre' => 'Quran', 'publisher' => 'mojawad.org'],

    // ── Visual branding ────────────────────────────────────────────────
    'video' => ['width' => 1920, 'height' => 1080, 'fps' => 25],
    'card_lab_dir' => 'published/card-lab',
    'social' => [
        'youtube' => env('SOCIAL_YOUTUBE', 'mojawad.net'),
        'facebook' => env('SOCIAL_FACEBOOK', 'mojawad.net'),
        'website' => env('SOCIAL_WEBSITE', 'mojawad.net'),
        'instagram' => env('SOCIAL_INSTAGRAM', 'mojawad.net'),
    ],
    'cover' => [
        'driver' => env('COVER_DRIVER', 'browsershot'),   // browsershot | ffmpeg
        'size' => 3000,
    ],
    'logo_path' => public_path('images/brand/logo-white.png'),
    'intro_video' => env('BRAND_INTRO_VIDEO'),
    'outro_video' => env('BRAND_OUTRO_VIDEO'),
    'render_timeout' => (int) env('PUBLISH_RENDER_TIMEOUT', 1800),

    // ── Podcast RSS ────────────────────────────────────────────────────
    'podcast' => [
        'title' => 'Mojawad — مجوّد',
        'description' => 'تلاوات قرآنية مجوّدة',
        'language' => 'ar',
        'author' => 'Mojawad',
        'email' => env('PODCAST_EMAIL'),
        'category' => 'Religion & Spirituality',
        'explicit' => false,
    ],

    // ── YouTube Data API ───────────────────────────────────────────────
    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
        'privacy_status' => env('YOUTUBE_PRIVACY', 'private'),
        'category_id' => '22',
        'made_for_kids' => false,
    ],

    // ── Facebook Graph API ─────────────────────────────────────────────
    'facebook' => [
        'page_id' => env('FACEBOOK_PAGE_ID'),
        'access_token' => env('FACEBOOK_PAGE_TOKEN'),
        'graph_version' => 'v21.0',
    ],
];
