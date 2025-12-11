<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Register')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Check if registration is allowed (no users exist).
     * Redirect to login if users already exist.
     */
    public function mount(): void
    {
        if (User::exists()) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('app.register_name_required'),
            'name.min' => __('app.register_name_min'),
            'email.required' => __('app.register_email_required'),
            'email.email' => __('app.register_email_invalid'),
            'password.required' => __('app.register_password_required'),
            'password.min' => __('app.register_password_min'),
            'password.confirmed' => __('app.register_password_confirmed'),
        ];
    }

    /**
     * Register the first user.
     * Uses database transaction with lock to prevent race conditions.
     */
    public function register(): void
    {
        $this->validate();

        // Use a database transaction with lock to prevent race conditions
        // This ensures only ONE user can ever be created through this form
        $user = DB::transaction(function () {
            // Double-check inside transaction - critical for security
            if (User::lockForUpdate()->exists()) {
                return null;
            }

            return User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
        });

        if ($user === null) {
            // Another user was created between mount() and register()
            // This is a race condition attempt - redirect to login
            $this->redirect(route('login'), navigate: true);

            return;
        }

        // Log in the newly created user
        Auth::login($user);

        // Regenerate session for security
        session()->regenerate();

        // Redirect to home
        $this->redirect(route('home'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        // Extra security check in render - redirect if users exist
        if (User::exists()) {
            $this->redirect(route('login'), navigate: true);
        }

        return view('livewire.auth.register');
    }
}
