<?php

use App\Services\PrayerLocator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Cache::forget('homepage_data');
    Http::preventStrayRequests();
    app()->setLocale('ar');
    $this->travelTo(CarbonImmutable::parse('2026-01-15 10:00', 'UTC'));
});

function prayerTimesFor(?string $location): array
{
    $request = test()->withCredentials();

    if ($location !== null) {
        $request = $request->withUnencryptedCookie(PrayerLocator::COOKIE, $location);
    }

    return $request->getJson(route('prayer-times'))->assertOk()->json();
}

function prayerClock(array $data, string $key): string
{
    return collect($data['prayers'])->firstWhere('key', $key)['time'];
}

it('falls back to Cairo without a stored location', function () {
    $data = prayerTimesFor(null);

    expect($data)->toMatchArray(['location' => 'Africa/Cairo', 'precise' => false, 'city' => null])
        ->and($data['prayers'])->toHaveCount(10)
        ->and(prayerClock($data, 'asr'))->toBe('2:58 م');
});

it('falls back to Cairo for an unreadable location', function (string $location) {
    expect(prayerTimesFor($location))->toMatchArray(['location' => 'Africa/Cairo', 'city' => null]);
})->with(['garbage', 'UTC', 'Etc/GMT-3', 'Mars/Olympus', 'Asia/Riyadh|999,39.83', '<script>/x']);

it('uses the browser timezone city when it is not Cairo', function () {
    $data = prayerTimesFor('Asia/Riyadh');

    expect($data)->toMatchArray(['location' => 'Asia/Riyadh', 'precise' => false, 'city' => 'الرياض'])
        ->and(prayerClock($data, 'fajr'))->toBe('5:17 ص');
});

it('hides the city for the Cairo timezone', function () {
    expect(prayerTimesFor('Africa/Cairo'))->toMatchArray(['location' => 'Africa/Cairo', 'city' => null]);
});

it('understands legacy timezone names browsers still report', function () {
    $data = prayerTimesFor('Asia/Calcutta');

    expect($data['location'])->toBe('Asia/Calcutta')
        ->and($data['city'])->not->toBeNull();
});

it('uses exact coordinates with the reverse geocoded city', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response(['address' => ['city' => 'مكة المكرمة', 'country_code' => 'sa']]),
    ]);

    $data = prayerTimesFor('Asia/Riyadh|21.42,39.83');

    expect($data)->toMatchArray(['location' => 'Asia/Riyadh|21.42,39.83', 'precise' => true, 'city' => 'مكة المكرمة'])
        ->and(prayerClock($data, 'fajr'))->toBe('5:41 ص')
        ->and(prayerClock($data, 'isha'))->toBe('7:29 م');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'lat=21.42') && str_contains($request->url(), 'accept-language=ar'));
});

it('caches the reverse geocoded place per point', function () {
    Http::fake(['*' => Http::response(['address' => ['town' => 'جدة', 'country_code' => 'sa']])]);

    prayerTimesFor('Asia/Riyadh|21.54,39.17');
    prayerTimesFor('Asia/Riyadh|21.54,39.17');

    Http::assertSentCount(1);
});

it('labels exact coordinates as your location when the lookup fails', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(prayerTimesFor('Asia/Riyadh|21.42,39.83'))->toMatchArray(['precise' => true, 'city' => 'موقعك']);
});

it('skips the lookup and the city inside Cairo', function () {
    Http::fake();

    expect(prayerTimesFor('Africa/Cairo|30.06,31.25'))->toMatchArray(['precise' => true, 'city' => null]);

    Http::assertNothingSent();
});

it('renders the stored location on the home page', function () {
    $prayer = $this->withUnencryptedCookie(PrayerLocator::COOKIE, 'Asia/Dubai')
        ->get(route('home'))
        ->assertOk()
        ->viewData('prayer');

    expect($prayer)->toMatchArray(['location' => 'Asia/Dubai', 'city' => 'دبي', 'stored' => true]);
});
