<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTilawaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'qari_id'         => 'required|exists:qaris,id',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'recorded_at'     => 'nullable|date',
            'recorded_place'  => 'nullable|string|max:255',
            'status'          => 'required|in:active,inactive,pending',
            'audio_tmp'       => 'nullable|string|exists:tmp_uploads,id',
            'cover_image_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'is_featured'     => 'boolean',
        ];
    }
}
