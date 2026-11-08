<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GoatOrigin;
use App\Http\Requests\GoatRequest;
use App\Http\Resources\GoatResource;
use App\Models\Goat;
use App\Services\GoatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoatController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Goat::class);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');

        $goats = Goat::query()
            ->with([
                'purchase' => fn ($query) => $query->with('supplier')->withSum('activePayments as paid_total', 'amount'),
                'activeSale' => fn ($query) => $query->with('customer')->withSum('activePayments as paid_total', 'amount'),
            ])
            ->search($search)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('code')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return GoatResource::collection($goats)->response();
    }

    public function store(GoatRequest $request, GoatService $service): JsonResponse
    {
        $this->authorize('create', Goat::class);

        $data = $request->validated();
        $data['origin'] = GoatOrigin::Peranakan->value;

        $goat = $service->create($data, $request->user());

        return response()->json([
            'message' => 'Kambing '.$goat->code.' berhasil ditambahkan.',
            'data' => new GoatResource($goat->load('mother')),
        ], 201);
    }

    public function show(Goat $goat): JsonResponse
    {
        $this->authorize('view', $goat);

        $goat->load([
            'purchase' => fn ($query) => $query->with('supplier')->withSum('activePayments as paid_total', 'amount'),
            'purchase.activePayments',
            'sales' => fn ($query) => $query
                ->with(['customer', 'payments'])
                ->withSum('activePayments as paid_total', 'amount')
                ->orderByDesc('date'),
            'children',
            'mother',
            'scanLogs' => fn ($query) => $query->latest()->limit(6),
        ]);

        return response()->json(['data' => new GoatResource($goat)]);
    }

    public function update(GoatRequest $request, Goat $goat, GoatService $service): JsonResponse
    {
        $this->authorize('update', $goat);

        $data = $request->validated();

        if ($goat->origin === GoatOrigin::Pembelian) {
            unset($data['origin'], $data['mother_id']);
        }

        $service->update($goat, $data, $request->user());

        return response()->json([
            'message' => 'Data kambing '.$goat->code.' berhasil diperbarui.',
            'data' => new GoatResource($goat->fresh()),
        ]);
    }

    public function destroy(Request $request, Goat $goat, GoatService $service): JsonResponse
    {
        $this->authorize('delete', $goat);

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        $service->archive($goat, $request->user(), $reason);

        return $this->message('Kambing '.$goat->code.' berhasil diarsipkan.');
    }

    public function barcode(string $barcode): JsonResponse
    {
        $this->authorize('viewAny', Goat::class);

        $normalized = strtoupper((string) preg_replace('/\s+/', '', $barcode));

        $goat = Goat::query()
            ->with([
                'purchase' => fn ($query) => $query->with('supplier')->withSum('activePayments as paid_total', 'amount'),
                'activeSale' => fn ($query) => $query->with('customer')->withSum('activePayments as paid_total', 'amount'),
                'mother',
            ])
            ->where(fn ($query) => $query->where('barcode', $normalized)->orWhere('code', $normalized))
            ->first();

        if (! $goat) {
            return $this->message('Kambing tidak ditemukan.', 404);
        }

        return response()->json(['data' => new GoatResource($goat)]);
    }
}
