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
            'qari_id' => 'required|exists:qaris,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'recorded_at' => 'nullable|date',
            'recorded_place' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,pending',
            'audio' => 'nullable|file|mimes:mp3,mpeg,ogg,wav|max:204800',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'is_featured' => 'boolean',
        ];
    }
}
