<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\VideoCardService;
use Livewire\Component;

class CardSocialSettings extends Component
{
    /** @var array<string, string> */
    public array $social = [
        'youtube' => '',
        'facebook' => '',
        'website' => '',
        'instagram' => '',
    ];

    public bool $saved = false;

    public function mount(VideoCardService $cards): void
    {
        $this->social = array_merge($this->social, $cards->socialHandles());
    }

    public function updatedSocial(): void
    {
        $this->saved = false;
    }

    public function save(): void
    {
        $social = array_map(fn (string $handle): string => trim($handle), $this->social);

        Setting::set(VideoCardService::SOCIAL_KEY, $social);

        $this->social = $social;
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.admin.card-social-settings');
    }
}
