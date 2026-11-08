<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    private function owner(): User
    {
        return User::query()->where('email', 'owner@kamsya.id')->firstOrFail();
    }

    public function test_login_returns_token_and_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@kamsya.id',
            'password' => 'kamsya123',
            'device_name' => 'flutter-test',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('token_type', 'Bearer');
        $response->assertJsonPath('data.email', 'owner@kamsya.id');
        $response->assertJsonPath('data.role', 'owner');
        $response->assertJsonStructure(['token', 'data' => ['id', 'name', 'email']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotNull($this->owner()->fresh()->last_login_at);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@kamsya.id',
            'password' => 'salah',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Email atau kata sandi tidak sesuai.');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_inactive_user(): void
    {
        $user = User::query()->where('email', 'petugas@kamsya.id')->firstOrFail();
        $user->forceFill(['is_active' => false])->save();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'petugas@kamsya.id',
            'password' => 'kamsya123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Akun Anda tidak aktif. Hubungi Owner untuk mengaktifkan kembali.');
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        Sanctum::actingAs($this->owner());

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', 'owner@kamsya.id');
        $response->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_current_token(): void
    {
        $token = $this->owner()->createToken('flutter-test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_update_profile(): void
    {
        Sanctum::actingAs($this->owner());

        $response = $this->putJson('/api/v1/auth/profile', [
            'name' => 'Owner Baru',
            'email' => 'owner@kamsya.id',
            'phone' => '0812-0000-0000',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Owner Baru');
        $this->assertSame('0812-0000-0000', $this->owner()->fresh()->phone);
    }

    public function test_user_can_update_password(): void
    {
        $user = $this->owner();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'kamsya123',
            'password' => 'kamsya45678',
            'password_confirmation' => 'kamsya45678',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('kamsya45678', $user->fresh()->password));
    }

    public function test_validation_errors_are_in_indonesian(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Data yang dikirim tidak valid.');
        $response->assertJsonPath('errors.email.0', 'Email wajib diisi.');
    }
}
