<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Login Page Access', function () {
    it('redirects guests to login page when accessing home', function () {
        User::factory()->create(); // Must have user for login to work

        $response = $this->get('/');

        $response->assertRedirect('/login');
    });

    it('displays login page for guests when users exist', function () {
        User::factory()->create(); // Must have user for login to render

        $response = $this->get('/login');

        $response->assertSuccessful();
        $response->assertSee(__('app.login_title'));
    });

    it('redirects to register when no users exist', function () {
        $response = $this->get('/login');

        $response->assertRedirect('/register');
    });

    it('renders login component successfully when users exist', function () {
        User::factory()->create(); // Must have user for login to render

        Livewire::test(Login::class)
            ->assertSuccessful()
            ->assertSee(__('app.login_title'));
    });

    it('redirects authenticated users away from login page', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/');
    });
});

describe('Login Functionality', function () {
    it('allows user to login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    });

    it('rejects login with wrong password', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee(__('app.login_failed'));

        $this->assertGuest();
    });

    it('rejects login with unknown email', function () {
        User::factory()->create(); // Must have user for login to render

        Livewire::test(Login::class)
            ->set('email', 'nonexistent@example.com')
            ->set('password', 'some-password')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee(__('app.login_failed'));

        $this->assertGuest();
    });

    it('supports remember me functionality', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    });
});

describe('Login Validation', function () {
    it('requires email field', function () {
        User::factory()->create(); // Must have user for login to render

        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', 'some-password')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);
    });

    it('requires valid email format', function () {
        User::factory()->create(); // Must have user for login to render

        Livewire::test(Login::class)
            ->set('email', 'not-an-email')
            ->set('password', 'some-password')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    });

    it('requires password field', function () {
        User::factory()->create(); // Must have user for login to render

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password' => 'required']);
    });
});

describe('Login Rate Limiting', function () {
    it('blocks login after too many failed attempts', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Clear any existing rate limits
        RateLimiter::clear('test@example.com|127.0.0.1');

        // Simulate 5 failed attempts to hit the rate limit
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->call('login');
        }

        // 6th attempt should be throttled
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    });
});

describe('Logout Functionality', function () {
    it('allows authenticated user to logout', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    });

    it('redirects guests who try to logout', function () {
        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/logout');

        $response->assertRedirect('/login');
    });
});
