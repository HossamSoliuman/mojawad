<?php

namespace App\Models;

use Database\Factories\FacebookCampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookCampaign extends Model
{
    /** @use HasFactory<FacebookCampaignFactory> */
    use HasFactory;

    /**
     * How many edit lessons are kept. The log is written to be pasted into a
     * generator prompt, so the oldest corrections fall off rather than letting
     * the instructions grow past what is worth reading.
     */
    public const EDIT_LESSON_LIMIT = 40;

    protected $fillable = [
        'name',
        'slug',
        'goal',
        'audience',
        'cadence',
        'tone',
        'core_hashtags',
        'image_workflow',
        'post_instructions',
        'edit_lessons',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class)->orderBy('sort_order')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The starting rule set for a campaign that has none written yet. These are
     * the writing rules the brief fields do not cover, so a generator handed the
     * instructions never has to guess them.
     */
    public static function defaultPostInstructions(): string
    {
        return implode("\n", [
            __('Do not end a post with an engagement request such as «شاركنا في التعليقات: أيّ تلاوةٍ للشيخ عبد الباسط تركت أثرًا في قلبك؟». Close with the meaning itself, or with a link to the recitation on Mojawad.'),
            __('Do not ask the reader to like, share, follow, or tag a friend.'),
            __('One idea per post — do not give the whole story away in the hook.'),
            __('A short post stays under 600 characters; a long post stays under 1500.'),
        ]);
    }

    public function postInstructions(): string
    {
        return filled($this->post_instructions)
            ? (string) $this->post_instructions
            : self::defaultPostInstructions();
    }

    /** @return list<string> */
    public function editLessonList(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R+/u', trim((string) $this->edit_lessons)) ?: []),
            fn (string $lesson): bool => $lesson !== '',
        ));
    }

    /**
     * Keep an editor's correction where the next generated post will see it.
     * Newest last, so the log reads as the campaign's history of fixes.
     */
    public function recordEditLesson(string $lesson): void
    {
        $lesson = trim(preg_replace('/\s+/u', ' ', $lesson) ?? '');

        if ($lesson === '') {
            return;
        }

        $lessons = array_merge($this->editLessonList(), [$lesson]);

        $this->forceFill([
            'edit_lessons' => implode("\n", array_slice($lessons, -self::EDIT_LESSON_LIMIT)),
        ])->save();
    }

    /**
     * The whole brief as one block of text: everything the generator needs to
     * write the next post, including the corrections made to the last ones.
     */
    public function instructionsDocument(): string
    {
        $sections = [
            __('Goal') => $this->goal,
            __('Audience') => $this->audience,
            __('Cadence') => $this->cadence,
            __('Tone guidelines') => $this->tone,
            __('Core hashtags') => $this->core_hashtags,
            __('Image workflow') => $this->image_workflow,
            __('Post writing instructions') => $this->postInstructions(),
        ];

        $document = collect($sections)
            ->filter(fn (?string $value): bool => filled($value))
            ->map(fn (string $value, string $heading): string => "## {$heading}\n".trim($value))
            ->prepend('# '.$this->name)
            ->values();

        if ($this->editLessonList() !== []) {
            $document->push(
                '## '.__('Lessons from editor corrections')."\n".
                collect($this->editLessonList())->map(fn (string $lesson): string => '- '.$lesson)->implode("\n")
            );
        }

        return $document->implode("\n\n");
    }
}
