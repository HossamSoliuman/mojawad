<?php

use App\Models\Tilawa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('matches tilawat by surah name', function () {
    // Surah 2 is "البقرة"; the tilawa title intentionally does not contain it.
    $tilawa = Tilawa::factory()->approved()->create([
        'title_ar' => 'تسجيل نادر',
        'title_en' => 'Rare recording',
        'surah_number' => 2,
    ]);

    $this->getJson(route('api.search', ['q' => 'البقرة']))
        ->assertOk()
        ->assertJsonFragment(['id' => $tilawa->id]);
});

it('returns nothing for short queries', function () {
    $this->getJson(route('api.search', ['q' => 'ا']))
        ->assertOk()
        ->assertJson(['qaris' => [], 'tilawat' => []]);
});
