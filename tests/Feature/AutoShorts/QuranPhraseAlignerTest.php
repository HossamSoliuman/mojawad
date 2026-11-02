<?php

use App\Services\QuranPhraseAligner;
use App\Support\ArabicSkeleton;
use App\Support\QuranCorpus;

/**
 * Timed transcript phrases: each starts at the given second and lasts 0.4s
 * per word.
 *
 * @param  array<int|string, string>  $phrases  start second => plain text
 * @return list<array{start: float, end: float, text: string}>
 */
function timedPhrases(array $phrases): array
{
    $timed = [];

    foreach ($phrases as $start => $text) {
        $timed[] = [
            'start' => (float) $start,
            'end' => $start + 0.4 * count(preg_split('/\s+/u', $text)) - 0.05,
            'text' => $text,
        ];
    }

    return $timed;
}

it('reduces mushaf and plain spellings to the same skeleton', function () {
    expect(ArabicSkeleton::of('ٱلرَّحۡمَٰنِ'))->toBe(ArabicSkeleton::of('الرحمن'))
        ->and(ArabicSkeleton::of('يَٰٓأَيُّهَا'))->toBe(ArabicSkeleton::of('يا').ArabicSkeleton::of('أيها'))
        ->and(ArabicSkeleton::indicDigits(13))->toBe('١٣');
});

it('maps a transcript onto the uthmani text with ayah numbers', function () {
    $segments = (new QuranPhraseAligner)->align([1], timedPhrases([
        0 => 'الحمد لله رب العالمين',
        5 => 'الرحمن الرحيم',
    ]));

    expect($segments)->toHaveCount(2)
        ->and($segments[0]['cues'][0]['text'])->toBe(QuranCorpus::ayat(1)[1].' ۝٢')
        ->and($segments[0]['opens'])->toBeTrue()
        ->and($segments[0]['closes'])->toBeTrue()
        ->and($segments[0]['start'])->toBe(0.0)
        ->and($segments[1]['cues'][0]['text'])->toBe(QuranCorpus::ayat(1)[2].' ۝٣')
        ->and($segments[1]['start'])->toBe(5.0);
});

it('shows a repeated passage again and marks a phrase that stops mid ayah', function () {
    $segments = (new QuranPhraseAligner)->align([1], timedPhrases([
        0 => 'صراط الذين أنعمت عليهم',
        4 => 'صراط الذين أنعمت عليهم',
        8 => 'غير المغضوب عليهم ولا الضالين',
    ]));

    expect($segments)->toHaveCount(3)
        ->and($segments[0]['cues'][0]['text'])->toBe($segments[1]['cues'][0]['text'])
        ->and($segments[0]['opens'])->toBeTrue()
        ->and($segments[0]['closes'])->toBeFalse()
        ->and($segments[2]['opens'])->toBeFalse()
        ->and($segments[2]['closes'])->toBeTrue()
        ->and($segments[2]['cues'][0]['text'])->toEndWith('۝٧');
});

it('numbers a refrain by the ayah that follows it', function () {
    $segments = (new QuranPhraseAligner)->align([55], timedPhrases([
        0 => 'فبأي آلاء ربكما تكذبان',
        4 => 'خلق الإنسان من صلصال كالفخار',
    ]));

    expect($segments[0]['cues'][0]['text'])->toEndWith('۝١٣')
        ->and($segments[1]['cues'][0]['text'])->toEndWith('۝١٤');
});

it('recognises the isti\'adha and flags speech that is not in the surah', function () {
    $segments = (new QuranPhraseAligner)->align([112], timedPhrases([
        0 => 'أعوذ بالله من الشيطان الرجيم',
        4 => 'يا سلام عليك يا شيخ',
        8 => 'قل هو الله أحد',
    ]));

    expect($segments)->toHaveCount(3)
        ->and($segments[0]['cues'][0]['text'])->toBe(QuranPhraseAligner::ISTIADHA)
        ->and($segments[0]['opens'])->toBeTrue()
        ->and($segments[1]['matched'])->toBeFalse()
        ->and($segments[1]['barrier'])->toBeTrue()
        ->and($segments[1]['cues'])->toBeEmpty()
        ->and($segments[2]['cues'][0]['text'])->toBe(QuranCorpus::ayat(112)[0].' ۝١');
});

it('splits a long phrase into several cues across its time', function () {
    $segments = (new QuranPhraseAligner(maxCueWords: 5))->align([2], timedPhrases([
        0 => 'ذلك الكتاب لا ريب فيه هدى للمتقين الذين يؤمنون بالغيب',
    ]));

    $cues = $segments[0]['cues'];

    expect($cues)->toHaveCount(3)
        ->and($cues[0]['start'])->toBe(0.0)
        ->and($cues[1]['start'])->toBe($cues[0]['end'])
        ->and($cues[1]['text'])->toEndWith('۝٢')
        ->and($cues[2]['start'])->toBe($cues[1]['end'])
        ->and(end($cues)['end'])->toBe($segments[0]['end']);
});

it('lets a short unmatched interjection through without a barrier', function () {
    $segments = (new QuranPhraseAligner)->align([112], timedPhrases([
        0 => 'قل هو الله أحد',
        4 => 'الله أكبر',
    ]));

    expect($segments[1]['matched'])->toBeFalse()
        ->and($segments[1]['barrier'])->toBeFalse();
});

it('breaks cues at ayah ends and moves the breaks onto the reciter\'s pauses', function () {
    $segments = (new QuranPhraseAligner)->align([112], [[
        'start' => 0.0,
        'end' => 12.0,
        'text' => 'قل هو الله أحد الله الصمد',
        'pauses' => [7.4],
    ]]);

    $cues = $segments[0]['cues'];

    expect($cues)->toHaveCount(2)
        ->and($cues[0]['text'])->toEndWith('۝١')
        ->and($cues[0]['end'])->toBe(7.4)
        ->and($cues[1]['start'])->toBe(7.4)
        ->and($cues[1]['text'])->toEndWith('۝٢');
});
