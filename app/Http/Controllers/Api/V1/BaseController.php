<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    protected function message(string $message, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(['message' => $message] + $extra, $status);
    }

    protected function perPage(Request $request, int $default = 20): int
    {
        $perPage = (int) $request->integer('per_page', $default);

        return max(1, min(100, $perPage ?: $default));
    }
}
