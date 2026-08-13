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
        'driver' => env('ASR_DRIVER', 'gemini'),   // gemini | openai
        'api_key' => env('ASR_API_KEY'),
        'base_url' => env('ASR_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('ASR_MODEL', 'whisper-1'),
        'timeout' => (int) env('ASR_TIMEOUT', 600),
        'window' => (int) env('ASR_WINDOW', 30),   // seconds of head/tail audio transcribed

        // Google Gemini backend. Flash models natively accept audio and the
        // Google AI Studio tier is free, so only the ~30s head/tail windows are
        // sent (never the full recitation) and both go in a single request.
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model' => env('ASR_GEMINI_MODEL', 'gemini-flash-latest'),
            'timeout' => (int) env('ASR_GEMINI_TIMEOUT', 120),
        ],
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
    'video' => ['width' => 1920, 'height' => 1080, 'fps' => 25, 'preset' => env('PUBLISH_VIDEO_PRESET', 'ultrafast')],
    'card_lab_dir' => 'published/card-lab',
    'cards_dir' => 'published/cards',

    // Card video motion: the qari photo drifts on a slow sine wave and the
    // optional extra text rises + fades in. Fractions are of frame height.
    'animation' => [
        'photo_drift' => 0.022,   // drift amplitude
        'photo_period' => 12.0,   // seconds per full up-down cycle
        'text_rise' => 0.083,     // how far below its slot the text starts
        'text_seconds' => 3.0,    // duration of the text entrance
    ],
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
    'browsershot' => [
        'chrome_path' => env(
            'BROWSERSHOT_CHROME_PATH',
            PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : null,
        ),
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

    // ── Social campaign ────────────────────────────────────────────────
    // Campaign copy is stored in the database. Generated images remain files,
    // with each file name stored on its facebook_posts record.
    'campaign_images' => env('SOCIAL_CAMPAIGN_IMAGES', base_path('docs/social/posters')),

    // ── Facebook Graph API ─────────────────────────────────────────────
    'facebook' => [
        'page_id' => env('FACEBOOK_PAGE_ID'),
        'access_token' => env('FACEBOOK_PAGE_TOKEN'),
        'graph_version' => 'v21.0',

        /*
        |------------------------------------------------------------------
        | Publishing cadence
        |------------------------------------------------------------------
        |
        | Approving a post books it into the next free slot below, so editors
        | never pick a date by hand. Two posts a day keeps each one competing
        | for a separate audience session — a heavier cadence mostly splits
        | the same reach across more posts. Friday earns a third slot before
        | Jumu'ah, the strongest window of the week for this audience.
        |
        | Times are local to `timezone` (the app itself runs on UTC) and are
        | stored converted, so the slot means the same thing year round.
        |
        */
        'cadence' => [
            'timezone' => env('FACEBOOK_CADENCE_TIMEZONE', 'Africa/Cairo'),
            'daily' => ['08:00', '21:00'],
            'friday' => ['08:00', '11:00', '21:00'],

            // Never book a slot closer than this, so the queue has runway.
            'lead_minutes' => (int) env('FACEBOOK_CADENCE_LEAD', 15),

            // How far ahead the booker will look before giving up.
            'horizon_days' => 180,
        ],
    ],
];
