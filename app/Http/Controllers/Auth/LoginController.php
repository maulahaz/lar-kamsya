<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.$seconds.' detik.']);
        }

        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($key, 60);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
        }

        if (! $user->is_active) {
            RateLimiter::hit($key, 60);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi Owner untuk mengaktifkan kembali.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        RateLimiter::clear($key);

        $user->activityLogs()->create([
            'action' => 'login',
            'description' => 'Masuk ke aplikasi',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->activityLogs()->create([
                'action' => 'logout',
                'description' => 'Keluar dari aplikasi',
                'ip_address' => $request->ip(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari aplikasi.');
    }
}
