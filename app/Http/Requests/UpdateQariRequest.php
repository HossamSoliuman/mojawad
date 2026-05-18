<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQariRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar'     => 'required|string|max:255',
            'name_en'     => 'nullable|string|max:255',
            'biography_ar'=> 'nullable|string',
            'biography_en'=> 'nullable|string',
            'status'      => 'required|in:active,inactive,pending',
            'image_tmp'   => 'nullable|string|exists:tmp_uploads,id',
            'is_featured' => 'boolean',
        ];
    }
}
