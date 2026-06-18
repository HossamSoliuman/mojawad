<?php

namespace App\Http\Requests;

use App\Models\VideoClip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClipStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', VideoClip::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tilawa_id' => 'required|exists:tilawat,id',
            'template' => ['required', 'string', Rule::in(config('clips.templates'))],
            'clip_start' => 'required|integer|min:0',
            'clip_duration' => ['required', 'integer', 'min:1', 'max:'.config('clips.max_duration')],
            'caption_ar' => 'nullable|string|max:2000',
            'overlay' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:8000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'overlay.required' => __('The video preview could not be captured. Please try again.'),
            'overlay.starts_with' => __('The video preview could not be captured. Please try again.'),
        ];
    }
}
