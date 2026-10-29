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
        $reference = CarbonImmutable::parse("{$date} {$clock}", PrayerTimes::TIMEZONE);

        expect(abs($times[$prayer]->diffInMinutes($reference)))->toBeLessThanOrEqual(2, "{$prayer} on {$date}");
    }
})->with('cairo reference times');

it('returns the five prayers in order on the requested Cairo day', function () {
    $times = (new PrayerTimes)->forDate(CarbonImmutable::parse('2026-06-21'));

    expect(array_keys($times))->toBe(['fajr', 'dhuhr', 'asr', 'maghrib', 'isha']);

    $previous = null;
    foreach ($times as $time) {
        expect($time->timezoneName)->toBe(PrayerTimes::TIMEZONE)
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
