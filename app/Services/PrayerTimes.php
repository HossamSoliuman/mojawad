<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Daily prayer times for Cairo using the Egyptian General Authority of
 * Survey method (Fajr 19.5°, Isha 17.5°, Shafi'i Asr), following the
 * praytimes.org solar-position algorithm.
 */
class PrayerTimes
{
    public const LATITUDE = 30.0444;

    public const LONGITUDE = 31.2357;

    public const TIMEZONE = 'Africa/Cairo';

    private const FAJR_ANGLE = 19.5;

    private const ISHA_ANGLE = 17.5;

    private const SUNSET_ANGLE = 0.833;

    private const ASR_SHADOW_FACTOR = 1;

    private const DHUHR_OFFSET_MINUTES = 1;

    /**
     * The five daily prayers in order, keyed by their translation label.
     *
     * @var array<string, string>
     */
    private const PRAYERS = [
        'fajr' => 'Fajr',
        'dhuhr' => 'Dhuhr',
        'asr' => 'Asr',
        'maghrib' => 'Maghrib',
        'isha' => 'Isha',
    ];

    private float $julianDate;

    /**
     * Prayer times for the given calendar day in Cairo.
     *
     * @return array{fajr: CarbonImmutable, dhuhr: CarbonImmutable, asr: CarbonImmutable, maghrib: CarbonImmutable, isha: CarbonImmutable}
     */
    public function forDate(CarbonInterface $date): array
    {
        $day = CarbonImmutable::create($date->year, $date->month, $date->day, 0, 0, 0, 'UTC');
        $this->julianDate = $this->julian($day->year, $day->month, $day->day) - self::LONGITUDE / (15 * 24);

        $hours = [
            'fajr' => $this->sunAngleTime(self::FAJR_ANGLE, 5 / 24, true),
            'dhuhr' => $this->midDay(12 / 24) + self::DHUHR_OFFSET_MINUTES / 60,
            'asr' => $this->asrTime(13 / 24),
            'maghrib' => $this->sunAngleTime(self::SUNSET_ANGLE, 18 / 24),
            'isha' => $this->sunAngleTime(self::ISHA_ANGLE, 18 / 24),
        ];

        return array_map(function (float $hour) use ($day): CarbonImmutable {
            $utcHour = $hour - self::LONGITUDE / 15;

            return $day->addSeconds((int) round($utcHour * 60) * 60)->setTimezone(self::TIMEZONE);
        }, $hours);
    }

    /**
     * Today's and tomorrow's prayers (Cairo calendar) for the hero countdown.
     *
     * @return list<array{key: string, name: string, at: int, time: string}>
     */
    public function upcoming(?CarbonInterface $now = null): array
    {
        $today = CarbonImmutable::instance($now ?? now())->setTimezone(self::TIMEZONE);
        $prayers = [];

        foreach ([$today, $today->addDay()] as $date) {
            foreach ($this->forDate($date) as $key => $time) {
                $prayers[] = [
                    'key' => $key,
                    'name' => __(self::PRAYERS[$key]),
                    'at' => $time->getTimestampMs(),
                    'time' => $time->format('g:i').' '.__($time->format('A')),
                ];
            }
        }

        return $prayers;
    }

    private function julian(int $year, int $month, int $day): float
    {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        $century = intdiv($year, 100);
        $leapCorrection = 2 - $century + intdiv($century, 4);

        return floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $leapCorrection - 1524.5;
    }

    /**
     * Sun declination and equation of time for a fraction of the day.
     *
     * @return array{declination: float, equation: float}
     */
    private function sunPosition(float $dayFraction): array
    {
        $days = $this->julianDate + $dayFraction - 2451545.0;
        $meanAnomaly = $this->fixAngle(357.529 + 0.98560028 * $days);
        $meanLongitude = $this->fixAngle(280.459 + 0.98564736 * $days);
        $eclipticLongitude = $this->fixAngle($meanLongitude + 1.915 * $this->sin($meanAnomaly) + 0.020 * $this->sin(2 * $meanAnomaly));
        $obliquity = 23.439 - 0.00000036 * $days;

        $rightAscension = rad2deg(atan2($this->cos($obliquity) * $this->sin($eclipticLongitude), $this->cos($eclipticLongitude))) / 15;

        return [
            'declination' => rad2deg(asin($this->sin($obliquity) * $this->sin($eclipticLongitude))),
            'equation' => $meanLongitude / 15 - $this->fixHour($rightAscension),
        ];
    }

    private function midDay(float $dayFraction): float
    {
        return $this->fixHour(12 - $this->sunPosition($dayFraction)['equation']);
    }

    private function sunAngleTime(float $angle, float $dayFraction, bool $beforeNoon = false): float
    {
        $declination = $this->sunPosition($dayFraction)['declination'];
        $offset = rad2deg(acos(
            (-$this->sin($angle) - $this->sin($declination) * $this->sin(self::LATITUDE))
            / ($this->cos($declination) * $this->cos(self::LATITUDE))
        )) / 15;

        return $this->midDay($dayFraction) + ($beforeNoon ? -$offset : $offset);
    }

    private function asrTime(float $dayFraction): float
    {
        $declination = $this->sunPosition($dayFraction)['declination'];
        $angle = -rad2deg(atan(1 / (self::ASR_SHADOW_FACTOR + tan(deg2rad(abs(self::LATITUDE - $declination))))));

        return $this->sunAngleTime($angle, $dayFraction);
    }

    private function sin(float $degrees): float
    {
        return sin(deg2rad($degrees));
    }

    private function cos(float $degrees): float
    {
        return cos(deg2rad($degrees));
    }

    private function fixAngle(float $angle): float
    {
        return $angle - 360 * floor($angle / 360);
    }

    private function fixHour(float $hour): float
    {
        return $hour - 24 * floor($hour / 24);
    }
}
