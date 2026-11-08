<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\ActivityLogger;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->withCount(['sales' => fn ($query) => $query->whereNull('voided_at')])
            ->withSum(['sales as sales_sum_price' => fn ($query) => $query->whereNull('voided_at')], 'price')
            ->with(['sales' => fn ($query) => $query->whereNull('voided_at')->with('goat')->latest('date')->latest('id')->limit(20)])
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
            'total' => Customer::query()->count(),
            'used' => Customer::query()->whereHas('sales', fn ($query) => $query->whereNull('voided_at'))->count(),
            'sale_value' => (float) \App\Models\Sale::query()->whereNull('voided_at')->sum('price'),
        ];

        return view('customers.index', compact('customers', 'search', 'stats'));
    }

    public function store(CustomerRequest $request, SequenceService $sequences, ActivityLogger $logger)
    {
        $this->authorize('create', Customer::class);

        $customer = DB::transaction(function () use ($request, $sequences, $logger) {
            $customer = Customer::query()->create([
                ...$request->validated(),
                'code' => $sequences->customerCode(),
            ]);

            $logger->log('create', $customer, 'Menambahkan customer '.$customer->name);

            return $customer;
        });

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer '.$customer->name.' berhasil ditambahkan.');
    }

    public function update(CustomerRequest $request, Customer $customer, ActivityLogger $logger)
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());
        $logger->log('update', $customer, 'Memperbarui customer '.$customer->name);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Data customer '.$customer->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Customer $customer, ActivityLogger $logger)
    {
        $this->authorize('delete', $customer);

        if ($customer->hasActiveTransactions()) {
            throw ValidationException::withMessages([
                'customer' => 'Customer masih memiliki penjualan aktif dan tidak dapat diarsipkan.',
            ]);
        }

        $logger->log('archive', $customer, 'Mengarsipkan customer '.$customer->name);
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer '.$customer->name.' berhasil diarsipkan.');
    }
}
