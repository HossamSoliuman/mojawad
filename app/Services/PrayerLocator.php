<?php

namespace App\Services;

use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use IntlDateFormatter;
use IntlTimeZone;
use Throwable;

/**
 * Resolves the visitor's browser location into the hero's prayer schedule.
 *
 * The location arrives as "<timezone>" (approximate, from the browser's
 * timezone) or "<timezone>|<lat>,<lng>" (precise, after the visitor
 * shares their position). Anything missing or invalid falls back to Cairo.
 */
class PrayerLocator
{
    public const COOKIE = 'prayer_location';

    private const CAIRO_RADIUS_KM = 20;

    private const PLACE_CACHE_DAYS = 90;

    /**
     * @return array{location: string, precise: bool, city: ?string, prayers: list<array{key: string, name: string, at: int, time: string}>}
     */
    public function resolve(?string $location): array
    {
        [$timezone, $coordinates] = $this->parse((string) $location);
        $zone = $timezone ? $this->zoneLocation($timezone) : null;

        if (! $zone) {
            return $this->cairo();
        }

        if ($coordinates) {
            [$latitude, $longitude] = $coordinates;
            $inCairo = $this->nearCairo($latitude, $longitude);
            $place = $inCairo ? null : $this->place($latitude, $longitude);
            $city = $inCairo ? null : ($place['city'] ?? __('Your location'));
            $times = PrayerTimes::forCountry($latitude, $longitude, $timezone, $place['country'] ?? $zone['country_code']);
            $key = sprintf('%s|%s,%s', $timezone, $this->coordinate($latitude), $this->coordinate($longitude));
        } elseif ($timezone === PrayerTimes::CAIRO_TIMEZONE) {
            return $this->cairo();
        } else {
            $times = PrayerTimes::forCountry($zone['latitude'], $zone['longitude'], $timezone, $zone['country_code']);
            $city = $this->zoneCity($timezone);
            $key = $timezone;
        }

        $prayers = $times->upcoming();

        if ($prayers === []) {
            return $this->cairo();
        }

        return ['location' => $key, 'precise' => $coordinates !== null, 'city' => $city, 'prayers' => $prayers];
    }

    /**
     * @return array{location: string, precise: bool, city: null, prayers: list<array{key: string, name: string, at: int, time: string}>}
     */
    private function cairo(): array
    {
        return [
            'location' => PrayerTimes::CAIRO_TIMEZONE,
            'precise' => false,
            'city' => null,
            'prayers' => (new PrayerTimes)->upcoming(),
        ];
    }

    /**
     * @return array{0: ?string, 1: array{0: float, 1: float}|null}
     */
    private function parse(string $location): array
    {
        if (! preg_match('~^([A-Za-z_]+(?:/[A-Za-z0-9_+\-]+){1,2})(?:\|(-?\d{1,2}(?:\.\d{1,2})?),(-?\d{1,3}(?:\.\d{1,2})?))?$~', $location, $match)) {
            return [null, null];
        }

        $coordinates = null;

        if (isset($match[2]) && abs((float) $match[2]) <= 90 && abs((float) $match[3]) <= 180) {
            $coordinates = [(float) $match[2], (float) $match[3]];
        }

        return [$match[1], $coordinates];
    }

    /**
     * Browsers report some zones by legacy names (e.g. Asia/Calcutta) that
     * carry no location, so those fall back to an equivalent current zone.
     *
     * @return array{country_code: string, latitude: float, longitude: float}|null
     */
    private function zoneLocation(string $timezone): ?array
    {
        $candidates = [$timezone];

        if (class_exists(IntlTimeZone::class)) {
            for ($i = 0, $count = IntlTimeZone::countEquivalentIDs($timezone); $i < $count; $i++) {
                $candidates[] = IntlTimeZone::getEquivalentID($timezone, $i);
            }
        }

        foreach ($candidates as $candidate) {
            try {
                $location = (new DateTimeZone($candidate))->getLocation();
            } catch (Throwable) {
                continue;
            }

            if ($location && ($location['country_code'] ?? '??') !== '??') {
                return $location;
            }
        }

        return null;
    }

    private function zoneCity(string $timezone): string
    {
        if (class_exists(IntlDateFormatter::class)) {
            $city = IntlDateFormatter::create(app()->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, null, 'VVV')?->format(0);

            if ($city) {
                return $city;
            }
        }

        return str_replace('_', ' ', last(explode('/', $timezone)));
    }

    /**
     * City and country for precise coordinates, cached per rounded point.
     *
     * @return array{city: ?string, country: ?string}|null
     */
    private function place(float $latitude, float $longitude): ?array
    {
        $cacheKey = sprintf('prayer_place:%s:%s,%s', app()->getLocale(), $this->coordinate($latitude), $this->coordinate($longitude));

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $response = Http::timeout(3)
                ->withHeaders(['User-Agent' => config('app.name').' ('.config('app.url').')'])
                ->get(rtrim(config('services.nominatim.url'), '/').'/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 10,
                    'accept-language' => app()->getLocale(),
                ]);
        } catch (Throwable) {
            return null;
        }

        $address = $response->successful() ? $response->json('address') : null;

        if (! is_array($address)) {
            return null;
        }

        $place = [
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? $address['state'] ?? null,
            'country' => isset($address['country_code']) ? strtoupper($address['country_code']) : null,
        ];

        Cache::put($cacheKey, $place, now()->addDays(self::PLACE_CACHE_DAYS));

        return $place;
    }

    private function nearCairo(float $latitude, float $longitude): bool
    {
        $latitudeDelta = deg2rad($latitude - PrayerTimes::CAIRO_LATITUDE);
        $longitudeDelta = deg2rad($longitude - PrayerTimes::CAIRO_LONGITUDE);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad(PrayerTimes::CAIRO_LATITUDE)) * cos(deg2rad($latitude)) * sin($longitudeDelta / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($a))) <= self::CAIRO_RADIUS_KM;
    }

    private function coordinate(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
