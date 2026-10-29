<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use IntlCalendar;

/**
 * Daily prayer times for any location, following the praytimes.org
 * solar-position algorithm. The calculation method follows the local
 * authority of the location's country (Egyptian survey by default for
 * Cairo, Umm al-Qura for Saudi Arabia, MWL where none is mapped).
 */
class PrayerTimes
{
    public const CAIRO_LATITUDE = 30.0444;

    public const CAIRO_LONGITUDE = 31.2357;

    public const CAIRO_TIMEZONE = 'Africa/Cairo';

    private const SUNSET_ANGLE = 0.833;

    private const ASR_SHADOW_FACTOR = 1;

    private const DHUHR_OFFSET_MINUTES = 1;

    /**
     * Fajr/Isha twilight angles, or a fixed Isha delay after Maghrib.
     *
     * @var array<string, array{fajr: float, isha?: float, isha_minutes?: int}>
     */
    private const METHODS = [
        'egyptian' => ['fajr' => 19.5, 'isha' => 17.5],
        'umm_al_qura' => ['fajr' => 18.5, 'isha_minutes' => 90],
        'gulf' => ['fajr' => 19.5, 'isha_minutes' => 90],
        'qatar' => ['fajr' => 18, 'isha_minutes' => 90],
        'kuwait' => ['fajr' => 18, 'isha' => 17.5],
        'jordan' => ['fajr' => 18, 'isha' => 18],
        'morocco' => ['fajr' => 19, 'isha' => 17],
        'algeria' => ['fajr' => 18, 'isha' => 17],
        'tunisia' => ['fajr' => 18, 'isha' => 18],
        'turkey' => ['fajr' => 18, 'isha' => 17],
        'karachi' => ['fajr' => 18, 'isha' => 18],
        'singapore' => ['fajr' => 20, 'isha' => 18],
        'isna' => ['fajr' => 15, 'isha' => 15],
        'mwl' => ['fajr' => 18, 'isha' => 17],
    ];

    /**
     * @var array<string, string>
     */
    private const COUNTRY_METHODS = [
        'EG' => 'egyptian', 'SD' => 'egyptian', 'LY' => 'egyptian', 'SY' => 'egyptian',
        'LB' => 'egyptian', 'IQ' => 'egyptian', 'PS' => 'egyptian',
        'SA' => 'umm_al_qura', 'YE' => 'umm_al_qura',
        'AE' => 'gulf', 'OM' => 'gulf', 'BH' => 'gulf',
        'QA' => 'qatar', 'KW' => 'kuwait', 'JO' => 'jordan',
        'MA' => 'morocco', 'DZ' => 'algeria', 'TN' => 'tunisia', 'TR' => 'turkey',
        'PK' => 'karachi', 'IN' => 'karachi', 'BD' => 'karachi', 'AF' => 'karachi',
        'SG' => 'singapore', 'MY' => 'singapore', 'ID' => 'singapore', 'BN' => 'singapore',
        'US' => 'isna', 'CA' => 'isna',
    ];

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

    public function __construct(
        public float $latitude = self::CAIRO_LATITUDE,
        public float $longitude = self::CAIRO_LONGITUDE,
        public string $timezone = self::CAIRO_TIMEZONE,
        public string $method = 'egyptian',
    ) {}

    public static function forCountry(float $latitude, float $longitude, string $timezone, ?string $countryCode): self
    {
        return new self($latitude, $longitude, $timezone, self::COUNTRY_METHODS[strtoupper((string) $countryCode)] ?? 'mwl');
    }

    /**
     * Prayer times for the given calendar day at this location, or null
     * when the sun never reaches the needed angles (polar day or night).
     *
     * @return array{fajr: CarbonImmutable, dhuhr: CarbonImmutable, asr: CarbonImmutable, maghrib: CarbonImmutable, isha: CarbonImmutable}|null
     */
    public function forDate(CarbonInterface $date): ?array
    {
        $day = CarbonImmutable::create($date->year, $date->month, $date->day, 0, 0, 0, 'UTC');
        $this->julianDate = $this->julian($day->year, $day->month, $day->day) - $this->longitude / (15 * 24);
        $method = self::METHODS[$this->method];

        $sunrise = $this->sunAngleTime(self::SUNSET_ANGLE, 6 / 24, true);
        $sunset = $this->sunAngleTime(self::SUNSET_ANGLE, 18 / 24);

        if (is_nan($sunrise) || is_nan($sunset)) {
            return null;
        }

        $night = 24 - ($sunset - $sunrise);
        $fajr = $this->sunAngleTime($method['fajr'], 5 / 24, true);
        $fajrPortion = $method['fajr'] / 60 * $night;

        if (is_nan($fajr) || $sunrise - $fajr > $fajrPortion) {
            $fajr = $sunrise - $fajrPortion;
        }

        if (isset($method['isha_minutes'])) {
            $isha = $sunset + $this->ishaDelayMinutes($method['isha_minutes'], $day) / 60;
        } else {
            $isha = $this->sunAngleTime($method['isha'], 18 / 24);
            $ishaPortion = $method['isha'] / 60 * $night;

            if (is_nan($isha) || $isha - $sunset > $ishaPortion) {
                $isha = $sunset + $ishaPortion;
            }
        }

        $hours = [
            'fajr' => $fajr,
            'dhuhr' => $this->midDay(12 / 24) + self::DHUHR_OFFSET_MINUTES / 60,
            'asr' => $this->asrTime(13 / 24),
            'maghrib' => $sunset,
            'isha' => $isha,
        ];

        return array_map(function (float $hour) use ($day): CarbonImmutable {
            $utcHour = $hour - $this->longitude / 15;

            return $day->addSeconds((int) round($utcHour * 60) * 60)->setTimezone($this->timezone);
        }, $hours);
    }

    /**
     * Today's and tomorrow's prayers (local calendar) for the hero countdown.
     *
     * @return list<array{key: string, name: string, at: int, time: string}>
     */
    public function upcoming(?CarbonInterface $now = null): array
    {
        $today = CarbonImmutable::instance($now ?? now())->setTimezone($this->timezone);
        $prayers = [];

        foreach ([$today, $today->addDay()] as $date) {
            foreach ($this->forDate($date) ?? [] as $key => $time) {
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

    /**
     * Umm al-Qura delays Isha by an extra 30 minutes during Ramadan.
     */
    private function ishaDelayMinutes(int $minutes, CarbonImmutable $day): int
    {
        if ($this->method !== 'umm_al_qura' || ! class_exists(IntlCalendar::class)) {
            return $minutes;
        }

        $hijri = IntlCalendar::createInstance('UTC', '@calendar=islamic-umalqura');
        $hijri->setTime($day->addHours(12)->getTimestampMs());

        return $hijri->get(IntlCalendar::FIELD_MONTH) === 8 ? $minutes + 30 : $minutes;
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
            (-$this->sin($angle) - $this->sin($declination) * $this->sin($this->latitude))
            / ($this->cos($declination) * $this->cos($this->latitude))
        )) / 15;

        return $this->midDay($dayFraction) + ($beforeNoon ? -$offset : $offset);
    }

    private function asrTime(float $dayFraction): float
    {
        $declination = $this->sunPosition($dayFraction)['declination'];
        $angle = -rad2deg(atan(1 / (self::ASR_SHADOW_FACTOR + tan(deg2rad(abs($this->latitude - $declination))))));

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
