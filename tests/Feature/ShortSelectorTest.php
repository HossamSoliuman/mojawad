<?php

use App\Models\Short;
use App\Models\ShortView;
use App\Models\TmpUpload;
use App\Models\User;
use App\Services\ShortSelector;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

function makeTmpShortUpload(User $user): TmpUpload
{
    $token = (string) Str::uuid();
    $path = 'tmp/uploads/'.$user->id.'/'.$token.'.mp4';
    Storage::disk('public')->put($path, 'binary-data');

    return TmpUpload::create([
        'id' => $token,
        'disk' => 'public',
        'path' => $path,
        'original_name' => 'clip.mp4',
        'mime' => 'video/mp4',
        'size' => 1024,
        'uploaded_by' => $user->id,
        'expires_at' => now()->addHour(),
    ]);
}

// ── ShortSelector::forViewer ──────────────────────────────────────────────────

it('returns the short with the fewest views for the viewer', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $a = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'sort_order' => 1]);
    $b = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'sort_order' => 2]);
    $c = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'sort_order' => 3]);

    $key = 'u:99';
    ShortView::create(['short_id' => $a->id, 'viewer_key' => $key, 'views' => 5, 'last_viewed_at' => now()]);
    ShortView::create(['short_id' => $b->id, 'viewer_key' => $key, 'views' => 1, 'last_viewed_at' => now()]);
    ShortView::create(['short_id' => $c->id, 'viewer_key' => $key, 'views' => 3, 'last_viewed_at' => now()]);

    $selected = app(ShortSelector::class)->forViewer($key);

    expect($selected?->id)->toBe($b->id);
});

it('returns a short with zero views before all other shorts', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $seen = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active']);
    $fresh = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active']);

    $key = 'u:100';
    ShortView::create(['short_id' => $seen->id, 'viewer_key' => $key, 'views' => 10, 'last_viewed_at' => now()]);

    $selected = app(ShortSelector::class)->forViewer($key);

    expect($selected?->id)->toBe($fresh->id);
});

it('returns null when no active shorts with media exist', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Short::factory()->create(['created_by' => $admin->id, 'status' => 'inactive']);

    expect(app(ShortSelector::class)->forViewer('u:1'))->toBeNull();
});

it('returns the currently pinned short for every viewer regardless of view counts', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'sort_order' => 0]);
    $pinned = Short::factory()->pinned()->create(['created_by' => $admin->id, 'status' => 'active', 'sort_order' => 99]);

    // Give pinned many views so it would normally lose.
    $key = 'u:55';
    ShortView::create(['short_id' => $pinned->id, 'viewer_key' => $key, 'views' => 100, 'last_viewed_at' => now()]);

    $selected = app(ShortSelector::class)->forViewer($key);

    expect($selected?->id)->toBe($pinned->id);
});

it('reverts to normal rotation once a pinned window expires', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $regular = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'sort_order' => 1]);
    Short::factory()->create([
        'created_by' => $admin->id,
        'status' => 'active',
        'sort_order' => 2,
        'pinned_starts_at' => now()->subHours(3),
        'pinned_ends_at' => now()->subMinute(),
    ]);

    $selected = app(ShortSelector::class)->forViewer('u:10');

    expect($selected?->id)->toBe($regular->id);
});

// ── POST /shorts/{short}/view endpoint ───────────────────────────────────────

it('records a view for an authenticated user', function () {
    $user = User::factory()->create()->assignRole('user');
    $short = Short::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('shorts.view', $short))
        ->assertNoContent();

    expect(ShortView::where('short_id', $short->id)->where('viewer_key', 'u:'.$user->id)->value('views'))
        ->toBe(1);
});

it('increments the view count on a second call for the same user', function () {
    $user = User::factory()->create()->assignRole('user');
    $short = Short::factory()->create(['status' => 'active']);

    $this->actingAs($user)->post(route('shorts.view', $short));
    $this->actingAs($user)->post(route('shorts.view', $short));

    expect(ShortView::where('short_id', $short->id)->where('viewer_key', 'u:'.$user->id)->value('views'))
        ->toBe(2);
});

it('records a view for a guest via cookie', function () {
    $short = Short::factory()->create(['status' => 'active']);
    $guestToken = 'abc-test-uuid';

    $this->withCookie('mojawad_vid', $guestToken)
        ->post(route('shorts.view', $short))
        ->assertNoContent();

    expect(ShortView::where('short_id', $short->id)->where('viewer_key', 'g:'.$guestToken)->value('views'))
        ->toBe(1);
});

it('does not record a view for an inactive short', function () {
    $short = Short::factory()->inactive()->create();

    $this->post(route('shorts.view', $short))->assertNoContent();

    expect(ShortView::count())->toBe(0);
});

// ── Admin: pin field persistence ─────────────────────────────────────────────

it('persists pinned window when creating a short', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $tmp = makeTmpShortUpload($admin);

    $this->actingAs($admin)
        ->post(route('admin.shorts.store'), [
            'source' => 'upload',
            'title_ar' => 'مقطع جمعة',
            'type' => 'video',
            'media_tmp' => $tmp->id,
            'sort_order' => 0,
            'status' => 'active',
            'pinned_starts_at' => '2026-07-04T06:00',
            'pinned_ends_at' => '2026-07-04T12:00',
        ])
        ->assertRedirect(route('admin.shorts.index'));

    $short = Short::first();
    expect($short->pinned_starts_at?->format('Y-m-d H:i'))->toBe('2026-07-04 06:00')
        ->and($short->pinned_ends_at?->format('Y-m-d H:i'))->toBe('2026-07-04 12:00');
});

it('validates that pinned_ends_at is after pinned_starts_at', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $tmp = makeTmpShortUpload($admin);

    $this->actingAs($admin)
        ->post(route('admin.shorts.store'), [
            'source' => 'upload',
            'title_ar' => 'مقطع',
            'type' => 'video',
            'media_tmp' => $tmp->id,
            'sort_order' => 0,
            'status' => 'active',
            'pinned_starts_at' => '2026-07-04T12:00',
            'pinned_ends_at' => '2026-07-04T06:00',
        ])
        ->assertSessionHasErrors('pinned_ends_at');
});
