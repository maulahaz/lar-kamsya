<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\ActivityLogger;
use App\Services\SequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->withCount(['sales' => fn ($query) => $query->whereNull('voided_at')])
            ->withSum(['sales as sales_sum_price' => fn ($query) => $query->whereNull('voided_at')], 'price')
            ->with(['sales' => fn ($query) => $query
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

        return CustomerResource::collection($customers)->response();
    }

    public function store(CustomerRequest $request, SequenceService $sequences, ActivityLogger $logger): JsonResponse
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

        return response()->json([
            'message' => 'Customer '.$customer->name.' berhasil ditambahkan.',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $customer->loadCount(['sales' => fn ($query) => $query->whereNull('voided_at')]);
        $customer->loadSum(['sales as sales_sum_price' => fn ($query) => $query->whereNull('voided_at')], 'price');
        $customer->load(['sales' => fn ($query) => $query
            ->whereNull('voided_at')
            ->withSum('activePayments as paid_total', 'amount')
            ->with('goat')
            ->latest('date')
            ->latest('id')
            ->limit(20)]);

        return response()->json(['data' => new CustomerResource($customer)]);
    }

    public function update(CustomerRequest $request, Customer $customer, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());
        $logger->log('update', $customer, 'Memperbarui customer '.$customer->name);

        return response()->json([
            'message' => 'Data customer '.$customer->name.' berhasil diperbarui.',
            'data' => new CustomerResource($customer->fresh()),
        ]);
    }

    public function destroy(Customer $customer, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('delete', $customer);

        if ($customer->hasActiveTransactions()) {
            throw ValidationException::withMessages([
                'customer' => 'Customer masih memiliki penjualan aktif dan tidak dapat diarsipkan.',
            ]);
        }

        $logger->log('archive', $customer, 'Mengarsipkan customer '.$customer->name);
        $customer->delete();

        return $this->message('Customer '.$customer->name.' berhasil diarsipkan.');
    }
}
