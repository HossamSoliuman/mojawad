<?php

use App\Models\Short;
use App\Models\TmpUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

function makeTmpUpload(User $user, string $name = 'clip.mp4'): TmpUpload
{
    $token = (string) Str::uuid();
    $path = 'tmp/uploads/'.$user->id.'/'.$token.'.'.pathinfo($name, PATHINFO_EXTENSION);
    Storage::disk('public')->put($path, 'binary-data');

    return TmpUpload::create([
        'id' => $token,
        'disk' => 'public',
        'path' => $path,
        'original_name' => $name,
        'mime' => 'video/mp4',
        'size' => 1024,
        'uploaded_by' => $user->id,
        'expires_at' => now()->addHour(),
    ]);
}

it('lets an admin open the shorts index', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.shorts.index'))->assertOk();
});

it('blocks regular users from the shorts index', function () {
    $user = User::factory()->create()->assignRole('user');

    $this->actingAs($user)->get(route('admin.shorts.index'))->assertForbidden();
});

it('creates a video short from a tmp upload', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tmp = makeTmpUpload($creator);

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'title_ar' => 'مقطع تجريبي',
            'title_en' => 'Test short',
            'type' => 'video',
            'media_tmp' => $tmp->id,
            'sort_order' => 5,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.shorts.index'));

    $short = Short::first();
    expect($short)->not->toBeNull()
        ->and($short->type)->toBe('video')
        ->and($short->created_by)->toBe($creator->id);

    Storage::disk('public')->assertExists($short->media_path);
    expect(TmpUpload::find($tmp->id))->toBeNull();
});

it('requires a media file when creating a short', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'title_ar' => 'مقطع',
            'type' => 'video',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('media_tmp');
});

it('prevents a creator from editing another creators short', function () {
    $owner = User::factory()->create()->assignRole('creator');
    $other = User::factory()->create()->assignRole('creator');
    $short = Short::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($other)->get(route('admin.shorts.edit', $short))->assertForbidden();
});

it('deletes a short and its media', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Storage::disk('public')->put('shorts/clip.mp4', 'x');
    $short = Short::factory()->create(['created_by' => $admin->id, 'media_path' => 'shorts/clip.mp4', 'poster_path' => null]);

    $this->actingAs($admin)
        ->delete(route('admin.shorts.destroy', $short))
        ->assertRedirect(route('admin.shorts.index'));

    expect(Short::find($short->id))->toBeNull();
    Storage::disk('public')->assertMissing('shorts/clip.mp4');
});

it('exposes active shorts to the home page and hides inactive ones', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $active = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'title_ar' => 'مقطع ظاهر']);
    $hidden = Short::factory()->create(['created_by' => $admin->id, 'status' => 'inactive', 'title_ar' => 'مقطع مخفي']);

    $response = $this->get(route('home'))->assertOk();

    $response->assertViewHas('hero_shorts', function (array $shorts) use ($active, $hidden) {
        $ids = array_column($shorts, 'id');

        return in_array($active->id, $ids, true) && ! in_array($hidden->id, $ids, true);
    });
});
