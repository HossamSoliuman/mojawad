<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QariResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'image_url'      => $this->image_url,
            'biography'      => $this->biography,
            'is_featured'    => $this->is_featured,
            'status'         => $this->status,
            'tilawat_count'  => $this->whenCounted('tilawat'),
            'created_at'     => $this->created_at->toDateString(),
        ];
    }
}
