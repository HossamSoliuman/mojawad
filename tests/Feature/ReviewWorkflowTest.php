<?php

use App\Livewire\Reviewer\ReviewQueue;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TmpUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

function userWithRole(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

it('puts a creator submission into pending review and keeps it off the public site', function () {
    Storage::fake('public');
    Storage::disk('public')->put('tmp/sample.mp3', 'audio-bytes');

    $creator = userWithRole('creator');
    $tmp = TmpUpload::create([
        'id' => 'tmp-token-1',
        'disk' => 'public',
        'path' => 'tmp/sample.mp3',
        'original_name' => 'sample.mp3',
        'mime' => 'audio/mpeg',
        'size' => 11,
        'uploaded_by' => $creator->id,
        'expires_at' => now()->addDay(),
    ]);

    $qari = Qari::factory()->create(['created_by' => $creator->id]);

    $this->actingAs($creator)
        ->post(route('admin.tilawat.store'), [
            'qari_id' => $qari->id,
            'title_ar' => 'سورة الفاتحة',
            'status' => 'active', // creator attempt to self-publish should be ignored
            'audio_tmp' => $tmp->id,
        ])
        ->assertRedirect(route('admin.tilawat.index'));

    $tilawa = Tilawa::firstWhere('title_ar', 'سورة الفاتحة');

    expect($tilawa->review_status)->toBe('pending')
        ->and($tilawa->status)->not->toBe('active');

    $this->assertDatabaseHas('tilawa_reviews', [
        'tilawa_id' => $tilawa->id,
        'action' => 'submitted',
    ]);
});

it('blocks non-reviewers from the review queue', function () {
    $this->actingAs(userWithRole('creator'))
        ->get(route('admin.review.index'))
        ->assertForbidden();
});

it('redirects a reviewer to the queue after login', function () {
    $reviewer = userWithRole('reviewer');
    $reviewer->forceFill(['password' => bcrypt('password')])->save();

    $this->post('/login', ['email' => $reviewer->email, 'password' => 'password'])
        ->assertRedirect(route('admin.review.index'));
});

it('approves a tilawa, publishes it, and logs the decision', function () {
    $reviewer = userWithRole('reviewer');
    $tilawa = Tilawa::factory()->create();

    Livewire::actingAs($reviewer)
        ->test(ReviewQueue::class)
        ->call('approve', $tilawa->id);

    $tilawa->refresh();

    expect($tilawa->review_status)->toBe('approved')
        ->and($tilawa->status)->toBe('active')
        ->and($tilawa->reviewed_by)->toBe($reviewer->id);

    $this->assertDatabaseHas('tilawa_reviews', [
        'tilawa_id' => $tilawa->id,
        'reviewer_id' => $reviewer->id,
        'action' => 'approved',
    ]);
});

it('requires a note to reject and stores it', function () {
    $reviewer = userWithRole('reviewer');
    $tilawa = Tilawa::factory()->create();

    Livewire::actingAs($reviewer)
        ->test(ReviewQueue::class)
        ->call('startReject', $tilawa->id)
        ->set('rejectionNote', '')
        ->call('reject', $tilawa->id)
        ->assertHasErrors('rejectionNote')
        ->set('rejectionNote', 'The qari is wrong, please fix.')
        ->call('reject', $tilawa->id)
        ->assertHasNoErrors();

    $tilawa->refresh();

    expect($tilawa->review_status)->toBe('rejected')
        ->and($tilawa->status)->toBe('inactive')
        ->and($tilawa->rejection_note)->toBe('The qari is wrong, please fix.');

    $this->assertDatabaseHas('tilawa_reviews', [
        'tilawa_id' => $tilawa->id,
        'action' => 'rejected',
        'note' => 'The qari is wrong, please fix.',
    ]);
});

it('sends a rejected tilawa back to pending when the creator resubmits', function () {
    $creator = userWithRole('creator');
    $tilawa = Tilawa::factory()->rejected('fix the title')->create([
        'uploaded_by' => $creator->id,
    ]);

    $this->actingAs($creator)
        ->put(route('admin.tilawat.update', $tilawa), [
            'qari_id' => $tilawa->qari_id,
            'title_ar' => 'Corrected title',
        ])
        ->assertRedirect(route('admin.tilawat.index'));

    $tilawa->refresh();

    expect($tilawa->review_status)->toBe('pending')
        ->and($tilawa->rejection_note)->toBeNull();

    $this->assertDatabaseHas('tilawa_reviews', [
        'tilawa_id' => $tilawa->id,
        'action' => 'resubmitted',
    ]);
});
