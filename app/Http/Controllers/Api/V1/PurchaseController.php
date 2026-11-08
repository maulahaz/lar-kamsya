<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Purchase::class);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('status', '');

        $query = Purchase::query()
            ->with(['goat', 'supplier'])
            ->withSum('activePayments as paid_total', 'amount')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like) {
                    $query->where('code', 'like', $like)
                        ->orWhereHas('goat', fn ($q) => $q->withTrashed()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('barcode', 'like', $like)))
                        ->orWhereHas('supplier', fn ($q) => $q->withTrashed()->where('name', 'like', $like));
                });
            })
            ->when($filter === 'voided', fn ($query) => $query->whereNotNull('voided_at'))
            ->when($filter === 'active', fn ($query) => $query->whereNull('voided_at'))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $totals = [
            'total' => (float) (clone $query)->sum('total'),
            'price' => (float) (clone $query)->sum('price'),
            'additional' => (float) (clone $query)->sum('additional_cost'),
            'count' => (clone $query)->count(),
        ];

        $purchases = $query->paginate($this->perPage($request))->withQueryString();

        return PurchaseResource::collection($purchases)->additional(['totals' => $totals])->response();
    }

    public function store(PurchaseRequest $request, PurchaseService $service): JsonResponse
    {
        $this->authorize('create', Purchase::class);

        $purchase = $service->create($request->validated(), $request->user());
        $purchase->loadSum('activePayments as paid_total', 'amount');

        return response()->json([
            'message' => 'Pembelian '.$purchase->code.' berhasil dicatat.',
            'data' => new PurchaseResource($purchase),
        ], 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $this->authorize('view', $purchase);

        $purchase->load([
            'goat.purchase.supplier',
            'goat.sales',
            'supplier',
            'creator',
            'payments' => fn ($query) => $query->with('creator')->orderBy('date'),
        ]);
        $purchase->loadSum('activePayments as paid_total', 'amount');

        return response()->json(['data' => new PurchaseResource($purchase)]);
    }

    public function void(Request $request, Purchase $purchase, PurchaseService $service): JsonResponse
    {
        $this->authorize('void', $purchase);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        $service->void($purchase, $reason, $request->user());

        $purchase = $purchase->fresh(['goat', 'supplier', 'creator']);
        $purchase->loadSum('activePayments as paid_total', 'amount');

        return response()->json([
            'message' => 'Pembelian '.$purchase->code.' berhasil di-void.',
            'data' => new PurchaseResource($purchase),
        ]);
    }
}
