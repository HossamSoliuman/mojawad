<?php

use App\Livewire\SurahTilawat;
use App\Models\Tilawa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders a valid surah page', function () {
    $this->get(route('surah.show', 2))->assertOk()->assertSee(config('surahs.2'));
});

it('rejects an out-of-range surah number', function () {
    $this->get(route('surah.show', 200))->assertNotFound();
});

it('only lists active tilawat for the requested surah', function () {
    $match = Tilawa::factory()->approved()->create(['surah_number' => 2]);
    $otherSurah = Tilawa::factory()->approved()->create(['surah_number' => 3]);
    $pending = Tilawa::factory()->create(['surah_number' => 2]);

    Livewire::test(SurahTilawat::class, ['surah' => 2])
        ->assertSee($match->title)
        ->assertDontSee($otherSurah->title)
        ->assertDontSee($pending->title);
});
