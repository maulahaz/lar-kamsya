<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\GoatResource;
use App\Http\Resources\ScanLogResource;
use App\Models\Goat;
use App\Models\ScanLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Goat::class);

        $code = trim((string) $request->query('code', ''));
        $goat = null;
        $found = null;

        if ($code !== '') {
            $normalized = strtoupper((string) preg_replace('/\s+/', '', $code));

            $goat = Goat::query()
                ->with([
                    'purchase' => fn ($query) => $query->with('supplier')->withSum('activePayments as paid_total', 'amount'),
                    'activeSale' => fn ($query) => $query->with('customer')->withSum('activePayments as paid_total', 'amount'),
                    'mother',
                ])
                ->where(fn ($query) => $query->where('barcode', $normalized)->orWhere('code', $normalized))
                ->first();

            $found = $goat !== null;

            ScanLog::query()->create([
                'user_id' => $request->user()->id,
                'goat_id' => $goat?->id,
                'barcode' => $normalized,
                'found' => $found,
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'data' => [
                'code' => $code,
                'found' => $found,
                'goat' => $goat ? new GoatResource($goat) : null,
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Goat::class);

        $logs = ScanLog::query()
            ->with('goat')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get();

        return ScanLogResource::collection($logs)->response();
    }
}
