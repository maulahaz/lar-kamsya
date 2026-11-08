<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Goat;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Purchase::class);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('status', '');

        $query = Purchase::query()
            ->with(['goat', 'supplier'])
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

        $purchases = $query->paginate(12)->withQueryString();

        return view('purchases.index', [
            'purchases' => $purchases,
            'totals' => $totals,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Purchase::class);

        return view('purchases.create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(PurchaseRequest $request, PurchaseService $service)
    {
        $this->authorize('create', Purchase::class);

        $purchase = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Pembelian '.$purchase->code.' berhasil dicatat.');
    }

    public function show(Purchase $purchase)
    {
        $this->authorize('view', $purchase);

        $purchase->load([
            'goat.purchase',
            'goat.sales',
            'supplier',
            'creator',
            'payments' => fn ($query) => $query->with('creator')->orderBy('date'),
        ]);

        return view('purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    public function void(Request $request, Purchase $purchase, PurchaseService $service)
    {
        $this->authorize('void', $purchase);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        try {
            $service->void($purchase, $reason, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Pembelian '.$purchase->code.' berhasil di-void.');
    }
}
