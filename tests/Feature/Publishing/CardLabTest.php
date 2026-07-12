<?php

use App\Livewire\Admin\CardLab;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use App\Services\VideoCardService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\mock;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

it('lets an admin open the card lab page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.publishing.card-lab'))->assertOk();
});

it('blocks regular users from the card lab page', function () {
    $user = User::factory()->create()->assignRole('user');

    $this->actingAs($user)->get(route('admin.publishing.card-lab'))->assertForbidden();
});

it('autofills the qari and surah names when a recitation is picked', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $qari = Qari::factory()->create(['name_ar' => 'الشيخ محمود خليل الحصري']);
    $tilawa = Tilawa::factory()->create(['qari_id' => $qari->id, 'surah_number' => 2]);

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->set('tilawaId', $tilawa->id)
        ->assertSet('qariName', 'الشيخ محمود خليل الحصري')
        ->assertSet('surahName', $tilawa->surah_name);
});

it('shows the typed names in the live card preview', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->set('qariName', 'الشيخ المنشاوي')
        ->set('surahName', 'الفاتحة')
        ->set('extraText', 'تلاوة نادرة')
        ->assertSee('الشيخ المنشاوي')
        ->assertSee('الفاتحة')
        ->assertSee('تلاوة نادرة');
});

it('stores the rendered card path and timing', function () {
    $admin = User::factory()->create()->assignRole('admin');
    mock(VideoCardService::class)
        ->shouldReceive('render')->once()->andReturn('published/card-lab/card.png')
        ->shouldReceive('dataUri')->andReturn(null)
        ->shouldReceive('html')->andReturn('<html></html>');

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->call('renderCard')
        ->assertSet('cardPath', 'published/card-lab/card.png')
        ->assertSet('error', null);
});

it('surfaces the render error when the card capture fails', function () {
    $admin = User::factory()->create()->assignRole('admin');
    mock(VideoCardService::class)
        ->shouldReceive('render')->once()->andThrow(new RuntimeException('Chrome not found'))
        ->shouldReceive('dataUri')->andReturn(null)
        ->shouldReceive('html')->andReturn('<html></html>');

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->call('renderCard')
        ->assertSet('cardPath', null)
        ->assertSet('error', 'Chrome not found');
});

it('renders a sample video from the card and the recitation audio', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $tilawa = Tilawa::factory()->create(['audio_path' => 'tilawat/audio.mp3']);
    mock(VideoCardService::class)
        ->shouldReceive('sampleVideo')->once()
        ->with('published/card-lab/card.png', 'tilawat/audio.mp3')
        ->andReturn('published/card-lab/sample.mp4')
        ->shouldReceive('html')->andReturn('<html></html>');

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->set('tilawaId', $tilawa->id)
        ->set('cardPath', 'published/card-lab/card.png')
        ->call('renderSample')
        ->assertSet('videoPath', 'published/card-lab/sample.mp4');
});

it('renders the card html with the social footer', function () {
    config(['publishing.social.youtube' => '@mojawad']);

    $html = app(VideoCardService::class)->html([
        'qariName' => 'الشيخ الحصري',
        'surahName' => 'البقرة',
    ]);

    expect($html)
        ->toContain('الشيخ الحصري')
        ->toContain('البقرة')
        ->toContain('@mojawad')
        ->toContain('mojawad.net');
});
