<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Register Page Access', function () {
    it('displays register page when no users exist', function () {
        // Ensure no users exist
        expect(User::count())->toBe(0);

        $response = $this->get('/register');

        $response->assertStatus(200);
    });

    it('renders register component when no users exist', function () {
        Livewire::test(Register::class)
            ->assertStatus(200)
            ->assertSee(__('app.register_title'));
    });

    it('redirects to login when users already exist', function () {
        User::factory()->create();

        Livewire::test(Register::class)
            ->assertRedirect(route('login'));
    });

    it('redirects to login via HTTP when users exist', function () {
        User::factory()->create();

        $this->get('/register')
            ->assertRedirect(route('login'));
    });
});

describe('Registration Functionality', function () {
    it('allows first user to register successfully', function () {
        expect(User::count())->toBe(0);

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('home'));

        expect(User::count())->toBe(1);
        expect(User::first()->email)->toBe('test@example.com');
        expect(User::first()->name)->toBe('Test User');
    });

    it('logs in user automatically after registration', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $this->assertAuthenticated();
    });

    it('hashes password correctly', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::first();
        expect($user->password)->not->toBe('password123');
        expect(\Illuminate\Support\Facades\Hash::check('password123', $user->password))->toBeTrue();
    });

    it('prevents registration when user already exists (race condition protection)', function () {
        // Simulate race condition by creating user after component mount but before register
        $component = Livewire::test(Register::class)
            ->set('name', 'Second User')
            ->set('email', 'second@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123');

        // Another user registers in between
        User::factory()->create(['email' => 'first@example.com']);

        // Try to register - should redirect to login
        $component->call('register')
            ->assertRedirect(route('login'));

        // Only the first user should exist
        expect(User::count())->toBe(1);
        expect(User::first()->email)->toBe('first@example.com');
    });
});

describe('Registration Validation', function () {
    it('requires name field', function () {
        Livewire::test(Register::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name' => 'required']);
    });

    it('requires name to be at least 2 characters', function () {
        Livewire::test(Register::class)
            ->set('name', 'A')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name' => 'min']);
    });

    it('requires email field', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'required']);
    });

    it('requires valid email format', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'email']);
    });

    it('requires password field', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['password' => 'required']);
    });

    it('requires password to be at least 8 characters', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('register')
            ->assertHasErrors(['password' => 'min']);
    });

    it('requires password confirmation to match', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different123')
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);
    });
});

describe('Login Redirect to Register', function () {
    it('redirects from login to register when no users exist', function () {
        expect(User::count())->toBe(0);

        Livewire::test(\App\Livewire\Auth\Login::class)
            ->assertRedirect(route('register'));
    });

    it('shows login page when users exist', function () {
        User::factory()->create();

        Livewire::test(\App\Livewire\Auth\Login::class)
            ->assertStatus(200)
            ->assertSee(__('app.login_title'));
    });
});
