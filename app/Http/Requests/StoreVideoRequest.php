<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'qari_id' => ['required', 'exists:qaris,id'],
            'surah_id' => ['required', 'exists:surahs,id'],
            'external_url' => ['nullable', 'url', 'required_without:file'],
            'file' => ['nullable', 'file', 'mimes:mp4,webm,mkv', 'max:204800', 'required_without:external_url'],
        ];
    }
}
