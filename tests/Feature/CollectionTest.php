<?php

use App\Models\Collection;
use App\Models\Tilawa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists active collections and hides inactive ones', function () {
    $active = Collection::create(['title_ar' => 'مجموعة نشطة', 'slug' => 'active-set', 'is_active' => true]);
    $hidden = Collection::create(['title_ar' => 'مجموعة مخفية', 'slug' => 'hidden-set', 'is_active' => false]);

    $this->get(route('collections.index'))
        ->assertOk()
        ->assertSee($active->title)
        ->assertDontSee($hidden->title);
});

it('shows an active collection with its active tilawat', function () {
    $collection = Collection::create(['title_ar' => 'تلاوات خاشعة', 'slug' => 'soothing', 'is_active' => true]);
    $tilawa = Tilawa::factory()->approved()->create();
    $collection->tilawat()->attach($tilawa->id, ['sort_order' => 0]);

    $this->get(route('collections.show', $collection))
        ->assertOk()
        ->assertSee($tilawa->title);
});

it('returns 404 for an inactive collection', function () {
    $collection = Collection::create(['title_ar' => 'مخفية', 'slug' => 'hidden', 'is_active' => false]);

    $this->get(route('collections.show', $collection))->assertNotFound();
});
