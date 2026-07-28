<?php

use App\Livewire\Admin\CardSocialSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\VideoCardService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Cache::flush();
});

it('prefills the footer handles with the config defaults', function () {
    config(['publishing.social' => [
        'youtube' => '@mojawad',
        'facebook' => 'mojawad',
        'website' => 'mojawad.net',
        'instagram' => '@mojawad',
    ]]);

    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(CardSocialSettings::class)
        ->assertSet('social.youtube', '@mojawad')
        ->assertSet('social.website', 'mojawad.net');
});

it('shows the footer links form at the top of the production page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.publishing.production'))
        ->assertOk()
        ->assertSeeLivewire('admin.card-social-settings');

    Livewire::test(CardSocialSettings::class)
        ->assertSeeHtml('wire:submit="save"');
});

it('saves the footer handles globally for every card', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(CardSocialSettings::class)
        ->set('social.youtube', '@mojawad-yt')
        ->set('social.facebook', 'mojawad.fb')
        ->call('save')
        ->assertSet('saved', true);

    expect(Setting::get(VideoCardService::SOCIAL_KEY))
        ->toMatchArray(['youtube' => '@mojawad-yt', 'facebook' => 'mojawad.fb']);

    expect(app(VideoCardService::class)->social())
        ->toMatchArray(['youtube' => '@mojawad-yt', 'facebook' => 'mojawad.fb']);
});

it('hides a platform on every card when its handle is cleared', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(CardSocialSettings::class)
        ->set('social.instagram', '')
        ->call('save');

    expect(app(VideoCardService::class)->social())->not->toHaveKey('instagram');
});

it('validates footer links before saving them', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(CardSocialSettings::class)
        ->set('social.youtube', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['social.youtube' => 'max']);
});
