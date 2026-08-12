<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->string('length')->default('short');
            $table->text('hook');
            $table->longText('body');
            $table->text('cta')->nullable();
            $table->text('hashtags')->nullable();
            $table->text('visual_brief')->nullable();
            $table->longText('image_prompt')->nullable();
            $table->string('aspect_ratio', 10)->default('1:1');
            $table->string('image_file')->nullable();
            $table->text('visual_text')->nullable();
            $table->text('alt_text')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('publish_slot')->nullable();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['facebook_campaign_id', 'status']);
        });

        $this->importLegacyCampaign();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_posts');
    }

    /**
     * Preserve the existing campaign once during deployment. The application
     * never reads the JSON file after these records have been imported.
     */
    private function importLegacyCampaign(): void
    {
        $path = base_path('docs/social/facebook-campaign.json');

        if (! is_file($path)) {
            return;
        }

        $legacy = json_decode((string) file_get_contents($path), true);

        if (! is_array($legacy) || ! is_array($legacy['posts'] ?? null)) {
            return;
        }

        $meta = is_array($legacy['campaign'] ?? null) ? $legacy['campaign'] : [];
        $name = trim((string) ($meta['name'] ?? 'Facebook campaign'));
        $now = now();
        $campaignId = DB::table('facebook_campaigns')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) ?: 'facebook-campaign',
            'goal' => $meta['goal'] ?? null,
            'audience' => $meta['audience'] ?? null,
            'cadence' => $meta['cadence'] ?? null,
            'tone' => $this->joinLines($meta['tone'] ?? null),
            'core_hashtags' => $this->joinWords($meta['core_hashtags'] ?? null),
            'image_workflow' => $meta['image_workflow'] ?? null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_values($legacy['posts']) as $position => $post) {
            if (! is_array($post) || blank($post['title'] ?? null)) {
                continue;
            }

            DB::table('facebook_posts')->insert([
                'facebook_campaign_id' => $campaignId,
                'title' => trim((string) $post['title']),
                'category' => filled($post['category'] ?? null) ? trim((string) $post['category']) : null,
                'status' => 'draft',
                'length' => in_array($post['length'] ?? null, ['short', 'long'], true) ? $post['length'] : 'short',
                'hook' => trim((string) ($post['hook'] ?? '')),
                'body' => $this->joinParagraphs($post['body'] ?? null),
                'cta' => filled($post['cta'] ?? null) ? trim((string) $post['cta']) : null,
                'hashtags' => $this->joinWords($post['hashtags'] ?? null),
                'visual_brief' => filled($post['visual_brief'] ?? null) ? trim((string) $post['visual_brief']) : null,
                'image_prompt' => $this->joinParagraphs($post['image_prompt'] ?? null),
                'aspect_ratio' => in_array($post['aspect_ratio'] ?? null, ['4:5', '1:1', '16:9'], true) ? $post['aspect_ratio'] : '1:1',
                'image_file' => filled($post['image_file'] ?? null) ? basename(trim((string) $post['image_file'])) : null,
                'visual_text' => filled($post['visual_text'] ?? null) ? trim((string) $post['visual_text']) : null,
                'alt_text' => filled($post['alt_text'] ?? null) ? trim((string) $post['alt_text']) : null,
                'review_notes' => $this->joinLines($post['verify'] ?? null),
                'publish_slot' => filled($post['publish_slot'] ?? null) ? trim((string) $post['publish_slot']) : null,
                'sort_order' => $position,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function joinParagraphs(mixed $value): string
    {
        return implode("\n\n", $this->stringList($value));
    }

    private function joinLines(mixed $value): ?string
    {
        $text = implode("\n", $this->stringList($value));

        return $text !== '' ? $text : null;
    }

    private function joinWords(mixed $value): ?string
    {
        $text = implode(' ', $this->stringList($value));

        return $text !== '' ? $text : null;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $values),
            fn (string $item): bool => $item !== '',
        ));
    }
};
