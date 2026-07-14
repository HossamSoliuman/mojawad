<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTilawaRequest extends FormRequest
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
        $tilawaId = $this->route('tilawa')?->id;

        return [
            'qari_id' => 'required|exists:qaris,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tilawat', 'slug')->ignore($tilawaId)],
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'surah_number' => 'nullable|integer|min:1|max:114',
            'ayah_from' => 'nullable|integer|min:1|max:300|required_with:ayah_to',
            'ayah_to' => 'nullable|integer|min:1|max:300|gte:ayah_from',
            'recorded_at' => 'nullable|date',
            'recorded_place' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,pending',
            'audio_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'cover_image_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'is_featured' => 'boolean',
        ];
    }
}
