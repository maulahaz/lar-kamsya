<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda tidak aktif. Hubungi Owner untuk mengaktifkan kembali.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
