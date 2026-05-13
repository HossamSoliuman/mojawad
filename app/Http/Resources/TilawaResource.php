<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TilawaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'recorded_at'     => $this->recorded_at?->toDateString(),
            'recorded_place'  => $this->recorded_place,
            'audio_url'       => $this->audio_url,
            'duration'        => $this->duration,
            'duration_label'  => $this->formatted_duration,
            'cover_url'       => $this->cover_url,
            'is_featured'     => $this->is_featured,
            'downloads_count' => $this->downloads_count,
            'likes_count'     => $this->likes_count,
            'status'          => $this->status,
            'created_at'      => $this->created_at->toDateString(),
            'qari'            => $this->whenLoaded('qari', fn () => [
                'id'        => $this->qari->id,
                'name'      => $this->qari->name,
                'slug'      => $this->qari->slug,
                'image_url' => $this->qari->image_url,
            ]),
        ];
    }
}
