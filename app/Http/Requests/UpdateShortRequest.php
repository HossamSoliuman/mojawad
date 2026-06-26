<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'type' => 'required|in:audio,video',
            'qari_id' => 'nullable|integer|exists:qaris,id',
            'media_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'poster_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'pinned_starts_at' => 'nullable|date',
            'pinned_ends_at' => 'nullable|date|after_or_equal:pinned_starts_at',
        ];
    }
}
