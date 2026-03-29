<?php

namespace App\Notifications;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VideoStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Video $video,
        public readonly string $status
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'video_id' => $this->video->id,
            'status' => $this->status,
            'message' => "Your recitation ({$this->video->surah->name_en} by {$this->video->qari->name_en}) has been {$this->status}.",
        ];
    }
}
