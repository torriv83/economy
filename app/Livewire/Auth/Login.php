<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Login')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Redirect to register if no users exist.
     */
    public function mount(): void
    {
        if (! User::exists()) {
            $this->redirect(route('register'), navigate: true);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('app.login_email_required'),
            'email.email' => __('app.login_email_invalid'),
            'password.required' => __('app.login_password_required'),
        ];
    }

    public function login(): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('email', __('app.login_throttle', [
                'seconds' => $seconds,
            ]));

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);

            $this->addError('email', __('app.login_failed'));

            return;
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirect(session()->pull('url.intended', route('home')));
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.auth.login');
    }
}
