<?php

use App\Services\PrayerTimes;
use Carbon\CarbonImmutable;

dataset('cairo reference times', [
    'winter' => ['2026-01-15', ['fajr' => '05:21', 'dhuhr' => '12:04', 'asr' => '14:57', 'maghrib' => '17:17', 'isha' => '18:39']],
    'after daylight saving' => ['2026-10-30', ['fajr' => '04:40', 'dhuhr' => '11:39', 'asr' => '14:48', 'maghrib' => '17:10', 'isha' => '18:28']],
]);

it('matches the Egyptian survey reference times within two minutes', function (string $date, array $expected) {
    $times = (new PrayerTimes)->forDate(CarbonImmutable::parse($date));

    foreach ($expected as $prayer => $clock) {
        $reference = CarbonImmutable::parse("{$date} {$clock}", PrayerTimes::CAIRO_TIMEZONE);

        expect(abs($times[$prayer]->diffInMinutes($reference)))->toBeLessThanOrEqual(2, "{$prayer} on {$date}");
    }
})->with('cairo reference times');

it('returns the five prayers in order on the requested Cairo day', function () {
    $times = (new PrayerTimes)->forDate(CarbonImmutable::parse('2026-06-21'));

    expect(array_keys($times))->toBe(['fajr', 'dhuhr', 'asr', 'maghrib', 'isha']);

    $previous = null;
    foreach ($times as $time) {
        expect($time->timezoneName)->toBe(PrayerTimes::CAIRO_TIMEZONE)
            ->and($time->toDateString())->toBe('2026-06-21');

        if ($previous) {
            expect($time->greaterThan($previous))->toBeTrue();
        }
        $previous = $time;
    }
});

it('follows Cairo daylight saving time', function () {
    $service = new PrayerTimes;

    $summerDhuhr = $service->forDate(CarbonImmutable::parse('2026-10-29'))['dhuhr'];
    $winterDhuhr = $service->forDate(CarbonImmutable::parse('2026-10-30'))['dhuhr'];

    expect($summerDhuhr->format('H'))->toBe('12')
        ->and($winterDhuhr->format('H'))->toBe('11');
});

it('uses Umm al-Qura for Saudi Arabia with a later Isha in Ramadan', function () {
    $makkah = PrayerTimes::forCountry(21.42, 39.83, 'Asia/Riyadh', 'SA');

    $regular = $makkah->forDate(CarbonImmutable::parse('2026-01-15'));
    $ramadan = $makkah->forDate(CarbonImmutable::parse('2026-03-01'));

    expect($makkah->method)->toBe('umm_al_qura')
        ->and($regular['fajr']->format('H:i'))->toBe('05:41')
        ->and($regular['maghrib']->diffInMinutes($regular['isha']))->toEqual(90)
        ->and($ramadan['maghrib']->diffInMinutes($ramadan['isha']))->toEqual(120);
});

it('falls back to the Muslim World League method for unmapped countries', function () {
    expect(PrayerTimes::forCountry(51.51, -0.13, 'Europe/London', 'GB')->method)->toBe('mwl')
        ->and(PrayerTimes::forCountry(30.04, 31.24, 'Africa/Cairo', 'eg')->method)->toBe('egyptian');
});

it('keeps fajr and isha inside the night at high latitudes in summer', function () {
    $times = PrayerTimes::forCountry(51.51, -0.13, 'Europe/London', 'GB')->forDate(CarbonImmutable::parse('2026-06-21'));

    expect($times['fajr']->format('H:i'))->toBe('02:31')
        ->and($times['isha']->format('H:i'))->toBe('23:27');
});

it('returns no times when the sun never sets', function () {
    expect(PrayerTimes::forCountry(69.65, 18.96, 'Europe/Oslo', 'NO')->forDate(CarbonImmutable::parse('2026-06-21')))->toBeNull();
});
