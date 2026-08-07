<?php

use App\Livewire\Admin\FacebookCampaign;
use App\Models\User;
use App\Services\SocialCampaign;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->campaignFile = storage_path('framework/testing/tmp-campaign.json');
    $this->imageDirectory = storage_path('framework/testing/tmp-campaign-images');

    File::ensureDirectoryExists($this->imageDirectory);

    file_put_contents($this->campaignFile, json_encode([
        'campaign' => [
            'name' => 'حملة تجريبية',
            'goal' => 'اختبار المكتبة',
            'audience' => 'متابع عربي',
            'core_hashtags' => ['#مجوّد'],
            '_schema' => ['id' => 'شرح لا يُعرض'],
        ],
        'categories' => [
            'hadith' => ['label' => 'حديث نبوي', 'icon' => 'fa-mosque', 'color' => '#b45309'],
            'tafseer' => ['label' => 'تفسير آية', 'icon' => 'fa-star-and-crescent', 'color' => '#1d4ed8'],
        ],
        'posts' => [
            [
                'id' => 'hadith-sample',
                'category' => 'hadith',
                'length' => 'short',
                'visual' => 'poster',
                'title' => 'حديث تجريبي',
                'hook' => 'جملة جاذبة',
                'body' => ['الفقرة الأولى', 'الفقرة الثانية'],
                'cta' => 'شاركنا رأيك',
                'hashtags' => ['#حديث', '#مجوّد'],
                'visual_brief' => 'بوستر مربع بخلفية خضراء',
                'image_prompt' => ['A square poster background.', 'Leave the centre empty.'],
                'aspect_ratio' => '1:1',
                'image_file' => 'hadith-sample.png',
                'visual_text' => 'نص البوستر',
                'alt_text' => 'وصف بديل',
                'verify' => ['راجع التخريج'],
                'publish_slot' => 'الاثنين ٧:٠٠ مساءً',
            ],
            [
                'id' => 'tafseer-sample',
                'category' => 'tafseer',
                'length' => 'long',
                'visual' => 'still',
                'title' => 'تفسير تجريبي',
                'hook' => 'مدخل التفسير',
                'body' => ['شرح الآية'],
                'cta' => '',
                'hashtags' => [],
                'visual_brief' => 'صورة أجواء بلا نص',
                'image_file' => '../outside.png',
            ],
        ],
    ], JSON_UNESCAPED_UNICODE));

    config([
        'publishing.campaign_file' => $this->campaignFile,
        'publishing.campaign_images' => $this->imageDirectory,
    ]);
});

afterEach(function () {
    @unlink($this->campaignFile);
    File::deleteDirectory($this->imageDirectory);
});

it('builds a ready-to-paste text from the campaign file', function () {
    $posts = app(SocialCampaign::class)->posts();

    expect($posts)->toHaveCount(2);

    expect($posts[0])
        ->id->toBe('hadith-sample')
        ->category_label->toBe('حديث نبوي')
        ->length->toBe('short')
        ->visual->toBe('poster')
        ->text->toBe("جملة جاذبة\n\nالفقرة الأولى\n\nالفقرة الثانية\n\nشاركنا رأيك\n\n#حديث #مجوّد");
});

it('drops empty blocks and defaults a missing visual to poster', function () {
    config(['publishing.campaign_file' => $file = storage_path('framework/testing/tmp-campaign-partial.json')]);

    file_put_contents($file, json_encode([
        'posts' => [['id' => 'bare', 'hook' => 'مدخل', 'body' => ['نص']]],
    ], JSON_UNESCAPED_UNICODE));

    $post = app(SocialCampaign::class)->posts()[0];

    expect($post)
        ->text->toBe("مدخل\n\nنص")
        ->visual->toBe('poster')
        ->length->toBe('short');

    @unlink($file);
});

it('hides the internal schema notes from the campaign brief', function () {
    expect(app(SocialCampaign::class)->meta())
        ->toHaveKey('goal')
        ->not->toHaveKey('_schema');
});

it('renders the posts with their poster or still label on the admin tab', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.social.facebook'))
        ->assertOk()
        ->assertSeeLivewire('admin.facebook-campaign');

    Livewire::actingAs($admin)->test(FacebookCampaign::class)
        ->assertSee('حديث تجريبي')
        ->assertSee('تفسير تجريبي')
        ->assertSee(__('Poster'))
        ->assertSee(__('Still'))
        ->assertSee('راجع التخريج');
});

it('filters the library by category, length, visual, and search', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaign::class)
        ->set('category', 'hadith')
        ->assertSee('حديث تجريبي')
        ->assertDontSee('تفسير تجريبي')
        ->call('resetFilters')
        ->set('length', 'long')
        ->assertSee('تفسير تجريبي')
        ->assertDontSee('حديث تجريبي')
        ->call('resetFilters')
        ->set('visual', 'still')
        ->assertSee('تفسير تجريبي')
        ->assertDontSee('حديث تجريبي')
        ->call('resetFilters')
        ->set('search', 'الفقرة الثانية')
        ->assertSee('حديث تجريبي')
        ->assertDontSee('تفسير تجريبي');
});

it('flags an image as pending until the generated file is dropped in the folder', function () {
    expect(app(SocialCampaign::class)->find('hadith-sample'))
        ->image_file->toBe('hadith-sample.png')
        ->image_path->toBe($this->imageDirectory.DIRECTORY_SEPARATOR.'hadith-sample.png')
        ->image_ready->toBeFalse()
        ->image_prompt->toBe("A square poster background.\n\nLeave the centre empty.")
        ->aspect_ratio->toBe('1:1');

    file_put_contents($this->imageDirectory.'/hadith-sample.png', 'png-bytes');

    expect(app(SocialCampaign::class)->find('hadith-sample'))
        ->image_ready->toBeTrue()
        ->image_version->toBeGreaterThan(0);
});

it('keeps a hand-edited file name inside the images folder', function () {
    expect(app(SocialCampaign::class)->find('tafseer-sample')['image_path'])
        ->toBe($this->imageDirectory.DIRECTORY_SEPARATOR.'outside.png');
});

it('previews and downloads the generated image, and 404s while it is missing', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.social.poster', 'hadith-sample'))
        ->assertNotFound();

    file_put_contents($this->imageDirectory.'/hadith-sample.png', 'png-bytes');

    $this->actingAs($admin)
        ->get(route('admin.social.poster', 'hadith-sample'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.social.poster.download', 'hadith-sample'))
        ->assertOk()
        ->assertDownload('hadith-sample.png');

    $this->actingAs($admin)
        ->get(route('admin.social.poster', 'no-such-post'))
        ->assertNotFound();
});

it('offers the prompt while the image is pending and the download once it lands', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaign::class)
        ->set('category', 'hadith')
        ->assertSee(__('Image not generated yet'))
        ->assertSee('hadith-sample.png')
        ->assertSee('Leave the centre empty.')
        ->assertDontSee(__('Download image'));

    file_put_contents($this->imageDirectory.'/hadith-sample.png', 'png-bytes');

    Livewire::actingAs($admin)->test(FacebookCampaign::class)
        ->set('category', 'hadith')
        ->assertSee(__('Download image'))
        ->assertSee(__('Show in folder'))
        ->assertSee(route('admin.social.poster.download', 'hadith-sample'), false)
        ->assertDontSee(__('Image not generated yet'));
});

it('narrows the list to posts still waiting for their image', function () {
    $admin = User::factory()->create()->assignRole('admin');

    file_put_contents($this->imageDirectory.'/hadith-sample.png', 'png-bytes');

    Livewire::actingAs($admin)->test(FacebookCampaign::class)
        ->set('needsImage', true)
        ->assertSee('تفسير تجريبي')
        ->assertDontSee('حديث تجريبي')
        ->call('resetFilters')
        ->assertSee('حديث تجريبي');
});

it('shows an empty state instead of failing when the campaign file is missing', function () {
    config(['publishing.campaign_file' => storage_path('framework/testing/does-not-exist.json')]);

    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaign::class)
        ->assertSee(__('The campaign file was not found.'));
});

it('keeps the shipped campaign file valid with five samples', function () {
    config(['publishing.campaign_file' => base_path('docs/social/facebook-campaign.json')]);

    $campaign = app(SocialCampaign::class);

    expect($campaign->posts())->toHaveCount(5);

    foreach ($campaign->posts() as $post) {
        expect($post['visual'])->toBeIn(['poster', 'still']);
        expect($post['length'])->toBeIn(['short', 'long']);
        expect($post['text'])->not->toBeEmpty();
        expect($post['visual_brief'])->not->toBeEmpty();
        expect($post['image_prompt'])->not->toBeEmpty();
        expect($post['image_file'])->toBe($post['id'].'.png');
        expect($post['aspect_ratio'])->toBeIn(['4:5', '1:1', '16:9']);
    }
})->skip(
    fn (): bool => ! is_file(base_path('docs/social/facebook-campaign.json')),
    'docs/ is gitignored — the campaign file is not present in this checkout.',
);
