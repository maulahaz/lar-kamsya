<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return $this->message(
                'Terlalu banyak percobaan masuk. Coba lagi dalam '.$seconds.' detik.',
                429
            );
        }

        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($key, 60);

            return $this->message('Email atau kata sandi tidak sesuai.', 401);
        }

        if (! $user->is_active) {
            RateLimiter::hit($key, 60);

            return $this->message(
                'Akun Anda tidak aktif. Hubungi Owner untuk mengaktifkan kembali.',
                403
            );
        }

        RateLimiter::clear($key);

        $user->forceFill(['last_login_at' => now()])->save();

        $user->activityLogs()->create([
            'action' => 'login',
            'description' => 'Masuk ke aplikasi melalui API',
            'ip_address' => $request->ip(),
        ]);

        $device = trim((string) $request->input('device_name', ''));
        $device = $device !== '' ? $device : (string) ($request->userAgent() ?: 'flutter-app');

        $token = $user->createToken($device);

        return response()->json([
            'message' => 'Berhasil masuk.',
            'data' => new UserResource($user),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken?->expires_at?->toIso8601String(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $user->activityLogs()->create([
            'action' => 'logout',
            'description' => 'Keluar dari aplikasi melalui API',
            'ip_address' => $request->ip(),
        ]);

        return $this->message('Anda telah keluar dari aplikasi.');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan pengguna lain.',
        ]);

        $user->update($data);

        $user->activityLogs()->create([
            'action' => 'update',
            'description' => 'Memperbarui profil sendiri melalui API',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $user->tokens()->whereKeyNot($current->getKey())->delete();
        }

        $user->activityLogs()->create([
            'action' => 'update',
            'description' => 'Memperbarui kata sandi melalui API',
            'ip_address' => $request->ip(),
        ]);

        return $this->message('Kata sandi berhasil diperbarui.');
    }
}
