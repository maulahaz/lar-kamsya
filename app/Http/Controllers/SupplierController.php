<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $search = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->withCount(['purchases' => fn ($query) => $query->whereNull('voided_at')])
            ->withSum(['purchases as purchases_sum_total' => fn ($query) => $query->whereNull('voided_at')], 'total')
            ->with(['purchases' => fn ($query) => $query->whereNull('voided_at')->with('goat')->latest('date')->latest('id')->limit(20)])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            })
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Supplier::query()->count(),
            'used' => Supplier::query()->whereHas('purchases', fn ($query) => $query->whereNull('voided_at'))->count(),
            'purchase_value' => (float) \App\Models\Purchase::query()->whereNull('voided_at')->sum('total'),
        ];

        return view('suppliers.index', compact('suppliers', 'search', 'stats'));
    }

    public function store(SupplierRequest $request, SequenceService $sequences, ActivityLogger $logger)
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

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier '.$supplier->name.' berhasil ditambahkan.');
    }

    public function update(SupplierRequest $request, Supplier $supplier, ActivityLogger $logger)
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());
        $logger->log('update', $supplier, 'Memperbarui supplier '.$supplier->name);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Data supplier '.$supplier->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Supplier $supplier, ActivityLogger $logger)
    {
        $this->authorize('delete', $supplier);

        if ($supplier->hasActiveTransactions()) {
            throw ValidationException::withMessages([
                'supplier' => 'Supplier masih memiliki pembelian aktif dan tidak dapat diarsipkan.',
            ]);
        }

        $logger->log('archive', $supplier, 'Mengarsipkan supplier '.$supplier->name);
        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier '.$supplier->name.' berhasil diarsipkan.');
    }
}
