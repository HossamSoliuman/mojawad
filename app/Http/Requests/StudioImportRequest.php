<?php

namespace App\Http\Requests;

use App\Services\YoutubeImportService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StudioImportRequest extends FormRequest
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
            'urls' => 'required|string|max:20000',
        ];
    }

    public function after(YoutubeImportService $youtube): array
    {
        return [
            function (Validator $validator) use ($youtube): void {
                if ($validator->errors()->has('urls')) {
                    return;
                }

                if ($youtube->parseUrls((string) $this->input('urls')) === []) {
                    $validator->errors()->add('urls', __('No valid YouTube links were found.'));
                }
            },
        ];
    }
}
