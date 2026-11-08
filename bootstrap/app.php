<?php

use App\Http\Middleware\EnsureApiUserIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetApiLocale;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AppServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'api.active' => EnsureApiUserIsActive::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
            SetApiLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Sesi berakhir atau token tidak valid. Silakan masuk kembali.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
                ], Response::HTTP_FORBIDDEN);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
                ], Response::HTTP_FORBIDDEN);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data yang dikirim tidak valid.',
                    'errors' => $exception->errors(),
                ], $exception->status);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Terlalu banyak permintaan. Silakan coba lagi beberapa saat lagi.',
                ], Response::HTTP_TOO_MANY_REQUESTS, $exception->getHeaders());
            }
        });
    })->create();
