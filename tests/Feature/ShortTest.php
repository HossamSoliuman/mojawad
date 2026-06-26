<?php

use App\Jobs\ImportTiktokShort;
use App\Models\Qari;
use App\Models\Short;
use App\Models\TmpUpload;
use App\Models\User;
use App\Services\TiktokImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

const TIKTOK_URL = 'https://www.tiktok.com/@quran/video/1234567890123456789';
const TIKTOK_URL_2 = 'https://vm.tiktok.com/ZMabcdef1/';

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
            'source' => 'upload',
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

it('queues a tiktok import linked to a qari and creates a pending short', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'source' => 'tiktok',
            'qari_id' => $qari->id,
            'urls' => TIKTOK_URL,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.shorts.index'));

    $short = Short::first();
    expect($short)->not->toBeNull()
        ->and($short->type)->toBe('video')
        ->and($short->media_path)->toBeNull()
        ->and($short->import_status)->toBe('pending')
        ->and($short->qari_id)->toBe($qari->id)
        ->and($short->source_url)->toBe(TIKTOK_URL)
        ->and($short->title_ar)->toBe($qari->name);

    Bus::assertDispatched(ImportTiktokShort::class, fn ($job) => $job->shortId === $short->id);
});

it('imports many tiktok urls as separate pending shorts linked to the qari', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'source' => 'tiktok',
            'qari_id' => $qari->id,
            'urls' => TIKTOK_URL."\n".TIKTOK_URL_2."\n".TIKTOK_URL, // duplicate is de-duped
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.shorts.index'));

    $shorts = Short::all();
    expect($shorts)->toHaveCount(2)
        ->and($shorts->pluck('source_url')->all())->toEqualCanonicalizing([TIKTOK_URL, TIKTOK_URL_2])
        ->and($shorts->every(fn (Short $s) => $s->qari_id === $qari->id))->toBeTrue();

    Bus::assertDispatchedTimes(ImportTiktokShort::class, 2);
});

it('imports tiktok urls from an uploaded link list', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $file = UploadedFile::fake()->createWithContent('links.txt', TIKTOK_URL."\n".TIKTOK_URL_2."\n");

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'source' => 'tiktok',
            'qari_id' => $qari->id,
            'urls' => '',
            'urls_file' => $file,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.shorts.index'));

    expect(Short::count())->toBe(2);
    Bus::assertDispatchedTimes(ImportTiktokShort::class, 2);
});

it('rejects an invalid tiktok url', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'source' => 'tiktok',
            'qari_id' => $qari->id,
            'urls' => 'https://example.com/not-a-tiktok',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('urls');

    expect(Short::count())->toBe(0);
});

it('requires a qari when importing from tiktok', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'source' => 'tiktok',
            'urls' => TIKTOK_URL,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('qari_id');

    expect(Short::count())->toBe(0);
});

it('downloads the tiktok video and titles the short from the caption', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $short = Short::factory()->create([
        'created_by' => $creator->id,
        'media_path' => null,
        'title_ar' => 'اسم القارئ',
        'source_url' => TIKTOK_URL,
        'import_status' => 'pending',
    ]);

    $this->mock(TiktokImportService::class)
        ->shouldReceive('download')
        ->once()
        ->with(TIKTOK_URL)
        ->andReturn(['path' => 'shorts/downloaded.mp4', 'title' => 'سورة الرحمن']);

    (new ImportTiktokShort($short->id))->handle(app(TiktokImportService::class));

    $short->refresh();
    expect($short->import_status)->toBe('completed')
        ->and($short->media_path)->toBe('shorts/downloaded.mp4')
        ->and($short->title_ar)->toBe('سورة الرحمن');
});

it('keeps the provisional title when tiktok has no caption', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $short = Short::factory()->create([
        'created_by' => $creator->id,
        'media_path' => null,
        'title_ar' => 'اسم القارئ',
        'source_url' => TIKTOK_URL,
        'import_status' => 'pending',
    ]);

    $this->mock(TiktokImportService::class)
        ->shouldReceive('download')
        ->once()
        ->andReturn(['path' => 'shorts/downloaded.mp4', 'title' => null]);

    (new ImportTiktokShort($short->id))->handle(app(TiktokImportService::class));

    $short->refresh();
    expect($short->media_path)->toBe('shorts/downloaded.mp4')
        ->and($short->title_ar)->toBe('اسم القارئ');
});

it('marks the short failed when the tiktok download throws', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $short = Short::factory()->create([
        'created_by' => $creator->id,
        'media_path' => null,
        'source_url' => TIKTOK_URL,
        'import_status' => 'pending',
    ]);

    (new ImportTiktokShort($short->id))->failed(new RuntimeException('yt-dlp exploded'));

    $short->refresh();
    expect($short->import_status)->toBe('failed')
        ->and($short->import_error)->toContain('yt-dlp exploded');
});

it('hides still-importing tiktok shorts from the home page', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Short::factory()->create([
        'created_by' => $admin->id,
        'status' => 'active',
        'media_path' => null,
        'import_status' => 'pending',
    ]);

    $this->get(route('home'))->assertOk()
        ->assertViewHas('hero_short', null);
});

it('requires a media file when creating a short', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)
        ->post(route('admin.shorts.store'), [
            'source' => 'upload',
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

it('exposes an active short to the home page and hides inactive ones', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $active = Short::factory()->create(['created_by' => $admin->id, 'status' => 'active', 'title_ar' => 'مقطع ظاهر']);
    Short::factory()->create(['created_by' => $admin->id, 'status' => 'inactive', 'title_ar' => 'مقطع مخفي']);

    $this->get(route('home'))->assertOk()
        ->assertViewHas('hero_short', fn ($payload) => $payload !== null && $payload['id'] === $active->id);
});
