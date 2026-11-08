<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\SequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $search = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->withCount(['purchases' => fn ($query) => $query->whereNull('voided_at')])
            ->withSum(['purchases as purchases_sum_total' => fn ($query) => $query->whereNull('voided_at')], 'total')
            ->with(['purchases' => fn ($query) => $query
                ->whereNull('voided_at')
                ->withSum('activePayments as paid_total', 'amount')
                ->with('goat')
                ->latest('date')
                ->latest('id')
                ->limit(20)])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            })
            ->orderBy('code')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return SupplierResource::collection($suppliers)->response();
    }

    public function store(SupplierRequest $request, SequenceService $sequences, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = DB::transaction(function () use ($request, $sequences, $logger) {
            $supplier = Supplier::query()->create([
                ...$request->validated(),
                'code' => $sequences->supplierCode(),
            ]);

            $logger->log('create', $supplier, 'Menambahkan supplier '.$supplier->name);

            return $supplier;
        });

        return response()->json([
            'message' => 'Supplier '.$supplier->name.' berhasil ditambahkan.',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $supplier->loadCount(['purchases' => fn ($query) => $query->whereNull('voided_at')]);
        $supplier->loadSum(['purchases as purchases_sum_total' => fn ($query) => $query->whereNull('voided_at')], 'total');
        $supplier->load(['purchases' => fn ($query) => $query
            ->whereNull('voided_at')
            ->withSum('activePayments as paid_total', 'amount')
            ->with('goat')
            ->latest('date')
            ->latest('id')
            ->limit(20)]);

        return response()->json(['data' => new SupplierResource($supplier)]);
    }

    public function update(SupplierRequest $request, Supplier $supplier, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());
        $logger->log('update', $supplier, 'Memperbarui supplier '.$supplier->name);

        return response()->json([
            'message' => 'Data supplier '.$supplier->name.' berhasil diperbarui.',
            'data' => new SupplierResource($supplier->fresh()),
        ]);
    }

    public function destroy(Supplier $supplier, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->hasActiveTransactions()) {
            throw ValidationException::withMessages([
                'supplier' => 'Supplier masih memiliki pembelian aktif dan tidak dapat diarsipkan.',
            ]);
        }

        $logger->log('archive', $supplier, 'Mengarsipkan supplier '.$supplier->name);
        $supplier->delete();

        return $this->message('Supplier '.$supplier->name.' berhasil diarsipkan.');
    }
}
