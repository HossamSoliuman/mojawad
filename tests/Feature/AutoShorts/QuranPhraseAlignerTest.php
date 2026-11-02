<?php

use App\Services\QuranPhraseAligner;
use App\Support\ArabicSkeleton;
use App\Support\QuranCorpus;

/**
 * Timed transcript words from plain phrases: each phrase starts at the given
 * second and its words are 0.4s apart, so phrases split at the gaps between.
 *
 * @param  array<int|string, string>  $phrases  start second => plain text
 * @return list<array{w: string, s: float, e: float}>
 */
function timedWords(array $phrases): array
{
    $words = [];

    foreach ($phrases as $start => $text) {
        $time = (float) $start;

        foreach (preg_split('/\s+/u', $text) as $word) {
            $words[] = ['w' => $word, 's' => $time, 'e' => $time + 0.35];
            $time += 0.4;
        }
    }

    return $words;
}

it('reduces mushaf and plain spellings to the same skeleton', function () {
    expect(ArabicSkeleton::of('ٱلرَّحۡمَٰنِ'))->toBe(ArabicSkeleton::of('الرحمن'))
        ->and(ArabicSkeleton::of('يَٰٓأَيُّهَا'))->toBe(ArabicSkeleton::of('يا').ArabicSkeleton::of('أيها'))
        ->and(ArabicSkeleton::indicDigits(13))->toBe('١٣');
});

it('maps a transcript onto the uthmani text with ayah numbers', function () {
    $segments = (new QuranPhraseAligner)->align([1], timedWords([
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
    $segments = (new QuranPhraseAligner)->align([1], timedWords([
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
    $segments = (new QuranPhraseAligner)->align([55], timedWords([
        0 => 'فبأي آلاء ربكما تكذبان',
        4 => 'خلق الإنسان من صلصال كالفخار',
    ]));

    expect($segments[0]['cues'][0]['text'])->toEndWith('۝١٣')
        ->and($segments[1]['cues'][0]['text'])->toEndWith('۝١٤');
});

it('recognises the isti\'adha and drops phrases that are not recitation', function () {
    $segments = (new QuranPhraseAligner)->align([112], timedWords([
        0 => 'أعوذ بالله من الشيطان الرجيم',
        4 => 'يا سلام عليك يا شيخ',
        8 => 'قل هو الله أحد',
    ]));

    expect($segments)->toHaveCount(2)
        ->and($segments[0]['cues'][0]['text'])->toBe(QuranPhraseAligner::ISTIADHA)
        ->and($segments[0]['opens'])->toBeTrue()
        ->and($segments[1]['cues'][0]['text'])->toBe(QuranCorpus::ayat(112)[0].' ۝١');
});

it('splits a long phrase into several cues across its time', function () {
    $segments = (new QuranPhraseAligner(maxCueWords: 5))->align([2], timedWords([
        0 => 'ذلك الكتاب لا ريب فيه هدى للمتقين الذين يؤمنون بالغيب',
    ]));

    $cues = $segments[0]['cues'];

    expect($cues)->toHaveCount(2)
        ->and($cues[0]['start'])->toBe(0.0)
        ->and($cues[1]['start'])->toBe($cues[0]['end'])
        ->and(end($cues)['end'])->toBe($segments[0]['end']);
});
