<?php

use App\Models\Qari;
use App\Models\Tilawa;
use App\Services\PrayerTimes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget('homepage_data');
});

it('renders the hero with the radio card only', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('Holy Qur\'an Radio'))
        ->assertDontSee(__('Broadcasting from Cairo'))
        ->assertDontSee(__('The Holy Qur\'an, with you everywhere'))
        ->assertDontSee(__('Listen to the live broadcast and explore recitations from your favorite reciters.'));
});

it('wires the radio card to the live radio engine', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('@quran-radio-state.window="sync($event.detail)"', false)
        ->assertSee('window._quranRadioEngine = () =>', false)
        ->assertSee('this.engine.setCardInView(entry.isIntersecting)', false)
        ->assertDontSee('8s5u5tpdtwzuv?_=', false);
});

it('renders the next prayer countdown in the hero', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('class="prayer-next"', false)
        ->assertSee('x-data="prayerCountdown(', false)
        ->assertSee("Alpine.data('prayerCountdown'", false)
        ->assertSee('this.remaining < 20 * 60000', false);
});

it('passes today and tomorrow prayers in Cairo time to the hero', function () {
    $this->travelTo(CarbonImmutable::parse('2026-01-15 10:00', PrayerTimes::CAIRO_TIMEZONE));
    app()->setLocale('ar');

    $prayer = $this->get(route('home'))->assertOk()->viewData('prayer');
    $prayers = $prayer['prayers'];

    expect($prayer)->toMatchArray(['location' => 'Africa/Cairo', 'precise' => false, 'city' => null, 'stored' => false])
        ->and($prayers)->toHaveCount(10)
        ->and(array_column($prayers, 'key'))->toBe(['fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'fajr', 'dhuhr', 'asr', 'maghrib', 'isha'])
        ->and($prayers[2]['name'])->toBe('العصر')
        ->and($prayers[2]['time'])->toBe('2:58 م')
        ->and($prayers[5]['at'])->toBeGreaterThan(now()->addDay()->startOfDay()->getTimestampMs())
        ->and(array_column($prayers, 'at'))->toBe(collect($prayers)->pluck('at')->sort()->values()->all());
});

it('lists the top qaris in their admin sort order', function () {
    Qari::factory()->create(['name_ar' => 'ثالث', 'status' => 'active', 'sort_order' => 30]);
    Qari::factory()->create(['name_ar' => 'أول', 'status' => 'active', 'sort_order' => 10]);
    Qari::factory()->create(['name_ar' => 'ثاني', 'status' => 'active', 'sort_order' => 20]);

    $topQaris = $this->get(route('home'))->assertOk()->viewData('top_qaris');

    expect($topQaris->pluck('name_ar')->all())->toBe(['أول', 'ثاني', 'ثالث']);
});

it('no longer renders the browse by surah section', function () {
    Tilawa::factory()->approved()->create(['surah_number' => 2]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee(__('Browse by Surah'))
        ->assertDontSee('surah-chip');
});

it('scopes the home content so desktop density styles apply only here', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('class="wrap home" id="homeContent"', false);
});

it('renders arrow controls for the continue listening rail', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('rail-arrow rail-prev', false)
        ->assertSee('rail-arrow rail-next', false)
        ->assertSee('x-ref="rail"', false);
});
