<?php

namespace App\Http\Requests;

use App\Models\VideoClip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AutoShortStoreRequest extends FormRequest
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
            'qari_id' => 'required|exists:qaris,id',
            'count' => 'required|integer|min:1|max:20',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qari_id.required' => __('Choose a qari.'),
            'count.max' => __('Queue at most 20 shorts at a time.'),
        ];
    }
}
