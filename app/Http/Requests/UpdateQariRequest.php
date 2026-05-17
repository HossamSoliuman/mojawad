<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQariRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'biography'   => 'nullable|string',
            'status'      => 'required|in:active,inactive,pending',
            'image_tmp'   => 'nullable|string|exists:tmp_uploads,id',
            'is_featured' => 'boolean',
        ];
    }
}
