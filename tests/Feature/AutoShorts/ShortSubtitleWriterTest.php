<?php

use App\Services\ShortSubtitleWriter;

it('writes an ass script with the header, brand and timed ayat', function () {
    $ass = (new ShortSubtitleWriter)->ass(
        [['start' => 1.5, 'end' => 4.25, 'text' => 'قُلۡ هُوَ ٱللَّهُ أَحَدٌ ۝١']],
        'الشيخ محمد رفعت',
        'سورة الإخلاص',
        30,
    );

    expect($ass)
        ->toContain('PlayResX: 1080')
        ->toContain('Style: Ayah,Amiri Quran')
        ->toContain('Dialogue: 0,0:00:00.00,0:00:30.00,Qari,,0,0,0,,{\fad(400,0)}الشيخ محمد رفعت')
        ->toContain('Dialogue: 0,0:00:00.00,0:00:30.00,Surah,,0,0,0,,{\fad(400,0)}سورة الإخلاص')
        ->toContain(',Brand,,0,0,0,,mojawad.com')
        ->toContain('Dialogue: 0,0:00:01.50,0:00:04.25,Ayah,,0,0,0,,{\fad(220,180)}قُلۡ هُوَ ٱللَّهُ أَحَدٌ ۝١');
});

it('strips characters that would break the ass script', function () {
    $ass = (new ShortSubtitleWriter)->ass(
        [['start' => 0, 'end' => 61.2, 'text' => "نص {\\b1}\nآخر"]],
        'قارئ',
        'سورة',
        62,
    );

    expect($ass)->toContain('0:00:00.00,0:01:01.20,Ayah,,0,0,0,,{\fad(220,180)}نص b1 آخر');
});
