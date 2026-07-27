<?php

use App\Livewire\Admin\CardLab;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use App\Services\VideoCardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\mock;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
    Cache::flush();
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
        ->assertSet('tilawaTitle', $tilawa->title_ar)
        ->assertSet('qariName', 'الشيخ محمود خليل الحصري')
        ->assertSet('surahName', $tilawa->surah_label);
});

it('shows the recitation title as the top text of the card', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->set('tilawaTitle', 'ما تيسر من سورة يوسف')
        ->assertSee('ما تيسر من سورة يوسف');

    $html = app(VideoCardService::class)->html(['tilawaTitle' => 'ما تيسر من سورة يوسف']);

    expect($html)
        ->toContain('class="title"')
        ->toContain('ما تيسر من سورة يوسف');
});

it('shows the rare recitation badge by default and hides it when cleared', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test(CardLab::class)
        ->assertSet('rareBadge', 'تلاوة نادرة')
        ->assertSee('تلاوة نادرة')
        ->set('rareBadge', '')
        ->assertDontSee('class="badge"', false);
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
        'rareBadge' => 'تلاوة نادرة',
    ]);

    expect($html)
        ->toContain('الشيخ الحصري')
        ->toContain('البقرة')
        ->toContain('@mojawad')
        ->toContain('mojawad.net')
        ->toContain('class="badge"')
        ->toContain('تلاوة نادرة');
});

it('colours the footer icons per platform', function () {
    config(['publishing.social' => [
        'youtube' => 'mojawad.net',
        'facebook' => 'mojawad.net',
        'website' => 'mojawad.net',
        'instagram' => 'mojawad.net',
    ]]);

    $html = app(VideoCardService::class)->html(['surahName' => 'البقرة']);

    expect($html)
        ->toContain('.soc.yt svg { fill: #FF0000; }')
        ->toContain('.soc.fb svg { fill: #1877F2; }')
        ->toContain('igGrad');
});

it('omits the badge markup when no rare label is given', function () {
    $html = app(VideoCardService::class)->html([
        'qariName' => 'الشيخ الحصري',
        'surahName' => 'البقرة',
    ]);

    expect($html)->not->toContain('class="badge"');
});
