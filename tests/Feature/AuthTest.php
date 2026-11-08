<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_home_redirects_to_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
        $response->assertStatus(302);
    }

    public function test_login_page_is_accessible_for_guest(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::query()->where('email', 'owner@kamsya.id')->first();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'owner@kamsya.id',
            'password' => 'kamsya123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::query()->where('email', 'owner@kamsya.id')->first());
    }

    public function test_login_with_invalid_credentials_returns_errors(): void
    {
        $response = $this->from(route('login'))->post('/login', [
            'email' => 'owner@kamsya.id',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_with_inactive_user_returns_error(): void
    {
        $user = User::query()->where('email', 'petugas@kamsya.id')->first();
        $user->forceFill(['is_active' => false])->save();

        $response = $this->from(route('login'))->post('/login', [
            'email' => 'petugas@kamsya.id',
            'password' => 'kamsya123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::query()->where('email', 'admin@kamsya.id')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_rate_limiter_blocks_after_five_failed_attempts(): void
    {
        RateLimiter::clear('owner@kamsya.id|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('login'))->post('/login', [
                'email' => 'owner@kamsya.id',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->from(route('login'))->post('/login', [
            'email' => 'owner@kamsya.id',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }
}
