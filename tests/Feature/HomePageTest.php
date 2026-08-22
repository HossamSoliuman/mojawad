<?php

use App\Models\Qari;
use App\Models\Tilawa;
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
