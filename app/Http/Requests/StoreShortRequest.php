<?php

namespace App\Http\Requests;

use App\Services\TiktokImportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreShortRequest extends FormRequest
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
            'source' => 'required|in:upload,tiktok',
            'title_ar' => 'required_if:source,upload|nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'type' => 'required_if:source,upload|in:audio,video',
            'media_tmp' => 'required_if:source,upload|nullable|string|exists:tmp_uploads,id',
            'poster_tmp' => 'nullable|string|exists:tmp_uploads,id',
            'qari_id' => 'required_if:source,tiktok|nullable|integer|exists:qaris,id',
            'urls' => 'nullable|string',
            'urls_file' => 'nullable|file|mimes:txt,csv|max:1024',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('source') !== 'tiktok') {
                return;
            }

            $blob = (string) $this->input('urls');

            if ($this->hasFile('urls_file')) {
                $blob .= "\n".(string) file_get_contents($this->file('urls_file')->getRealPath());
            }

            if (app(TiktokImportService::class)->parseUrls($blob) === []) {
                $validator->errors()->add('urls', __('Add at least one valid TikTok video URL.'));
            }
        });
    }
}
