<?php

namespace App\Http\Controllers;

use App\Enums\GoatStatus;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Goat;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('status', '');

        $query = Sale::query()
            ->with(['goat', 'customer'])
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

        $sales = $query->paginate(12)->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'totals' => $totals,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Sale::class);

        $goats = Goat::query()
            ->where('status', GoatStatus::Tersedia->value)
            ->with('purchase')
            ->orderBy('code')
            ->get();

        return view('sales.create', [
            'goats' => $goats,
            'customers' => Customer::query()->orderBy('name')->get(),
            'selectedGoat' => $request->integer('goat') ?: null,
        ]);
    }

    public function store(SaleRequest $request, SaleService $service)
    {
        $this->authorize('create', Sale::class);

        $sale = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Penjualan '.$sale->code.' berhasil dicatat.');
    }

    public function show(Sale $sale)
    {
        $this->authorize('view', $sale);

        $sale->load([
            'goat.purchase.supplier',
            'customer',
            'creator',
            'payments' => fn ($query) => $query->with('creator')->orderBy('date'),
        ]);

        return view('sales.show', [
            'sale' => $sale,
        ]);
    }

    public function void(Request $request, Sale $sale, SaleService $service)
    {
        $this->authorize('void', $sale);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        try {
            $service->void($sale, $reason, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Penjualan '.$sale->code.' berhasil di-void.');
    }
}
