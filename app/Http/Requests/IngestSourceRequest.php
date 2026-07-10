<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IngestSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'creator']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'qari_id' => 'required|exists:qaris,id',
            'surah_number' => 'required|integer|between:1,114',
            'audio_tmp' => 'required|string|exists:tmp_uploads,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio_tmp.required' => __('Please upload a source audio file.'),
            'audio_tmp.exists' => __('The uploaded file has expired. Please upload it again.'),
        ];
    }
}
