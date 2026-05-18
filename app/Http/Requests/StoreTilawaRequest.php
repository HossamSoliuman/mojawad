<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTilawaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qari_id'         => 'required|exists:qaris,id',
            'title_ar'        => 'required|string|max:255',
            'title_en'        => 'nullable|string|max:255',
            'description_ar'  => 'nullable|string',
            'description_en'  => 'nullable|string',
            'recorded_at'     => 'nullable|date',
            'recorded_place'  => 'nullable|string|max:255',
            'status'          => 'required|in:active,inactive,pending',
            'audio_tmp'       => 'required|string|exists:tmp_uploads,id',
            'cover_image_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'is_featured'     => 'boolean',
        ];
    }
}
