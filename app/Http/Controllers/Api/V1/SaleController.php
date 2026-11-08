<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\SaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('status', '');

        $query = Sale::query()
            ->with(['goat', 'customer'])
            ->withSum('activePayments as paid_total', 'amount')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like) {
                    $query->where('code', 'like', $like)
                        ->orWhereHas('goat', fn ($q) => $q->withTrashed()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('barcode', 'like', $like)))
                        ->orWhereHas('customer', fn ($q) => $q->withTrashed()->where('name', 'like', $like));
                });
            })
            ->when($filter === 'voided', fn ($query) => $query->whereNotNull('voided_at'))
            ->when($filter === 'active', fn ($query) => $query->whereNull('voided_at'))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $totals = [
            'total' => (float) (clone $query)->sum('price'),
            'cogs' => (float) (clone $query)->sum('cost_price'),
            'count' => (clone $query)->count(),
        ];

        $sales = $query->paginate($this->perPage($request))->withQueryString();

        return SaleResource::collection($sales)->additional(['totals' => $totals])->response();
    }

    public function store(SaleRequest $request, SaleService $service): JsonResponse
    {
        $this->authorize('create', Sale::class);

        $sale = $service->create($request->validated(), $request->user());
        $sale->loadSum('activePayments as paid_total', 'amount');

        return response()->json([
            'message' => 'Penjualan '.$sale->code.' berhasil dicatat.',
            'data' => new SaleResource($sale),
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        $this->authorize('view', $sale);

        $sale->load([
            'goat.purchase.supplier',
            'customer',
            'creator',
            'payments' => fn ($query) => $query->with('creator')->orderBy('date'),
        ]);
        $sale->loadSum('activePayments as paid_total', 'amount');

        return response()->json(['data' => new SaleResource($sale)]);
    }

    public function void(Request $request, Sale $sale, SaleService $service): JsonResponse
    {
        $this->authorize('void', $sale);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        $service->void($sale, $reason, $request->user());

        $sale = $sale->fresh(['goat', 'customer', 'creator']);
        $sale->loadSum('activePayments as paid_total', 'amount');

        return response()->json([
            'message' => 'Penjualan '.$sale->code.' berhasil di-void.',
            'data' => new SaleResource($sale),
        ]);
    }
}
