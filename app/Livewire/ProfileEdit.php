<?php

namespace App\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfileEdit extends Component
{
    public bool $open = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function save(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $this->reset('password', 'password_confirmation');
        $this->open = false;

        session()->flash('success', __('Your account has been updated.'));

        $this->redirectRoute('profile', navigate: true);
    }

    public function render()
    {
        return view('livewire.profile-edit');
    }
}
