<?php

namespace App\Livewire\Auth;

use App\Actions\CreateOrganization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.guest')]
class Register extends Component
{
    #[Rule('required|string|max:200')]
    public string $organization_name = '';

    #[Rule('required|string|max:200')]
    public string $name = '';

    #[Rule('required|string|regex:/^09[0-9]{9}$/|unique:users,mobile')]
    public string $mobile = '';

    #[Rule('required|string|min:6|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function register(CreateOrganization $createOrganization): void
    {
        $this->validate();

        $admin = $createOrganization->handle(
            $this->organization_name,
            $this->name,
            $this->mobile,
            $this->password,
        );

        Auth::login($admin);

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')->title('ثبت‌نام');
    }
}
