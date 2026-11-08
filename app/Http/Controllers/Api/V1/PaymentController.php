<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('manage-transactions');

        $filter = (string) $request->query('status', '');
        $type = (string) $request->query('payable_type', '');
        $from = $request->query('from');
        $to = $request->query('to');

        $payments = Payment::query()
            ->with(['payable', 'creator'])
            ->when(in_array($type, ['purchase', 'sale'], true), fn ($query) => $query->where('payable_type', $type))
            ->when($filter === 'voided', fn ($query) => $query->whereNotNull('voided_at'))
            ->when($filter === 'active', fn ($query) => $query->whereNull('voided_at'))
            ->when($from, fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PaymentResource::collection($payments)->response();
    }

    public function show(Payment $payment): JsonResponse
    {
        Gate::authorize('manage-transactions');

        $payment->load(['payable', 'creator']);

        return response()->json(['data' => new PaymentResource($payment)]);
    }

    public function store(PaymentRequest $request, PaymentService $service): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'payable_type' => ['required', 'in:purchase,sale'],
            'payable_id' => ['required', 'integer'],
        ], [
            'payable_type.required' => 'Jenis transaksi pembayaran wajib diisi.',
            'payable_type.in' => 'Jenis transaksi pembayaran tidak dikenali.',
            'payable_id.required' => 'Transaksi pembayaran wajib dipilih.',
        ]);

        $payable = $data['payable_type'] === 'sale'
            ? Sale::query()->findOrFail($data['payable_id'])
            : Purchase::query()->findOrFail($data['payable_id']);

        $payment = $service->record($payable, $request->validated(), $request->user());
        $payment->load(['payable', 'creator']);

        return response()->json([
            'message' => 'Pembayaran '.$payment->code.' berhasil dicatat.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function void(Request $request, Payment $payment, PaymentService $service): JsonResponse
    {
        $this->authorize('void', $payment);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        $service->void($payment, $reason, $request->user());
        $payment = $payment->fresh(['payable', 'creator']);

        return response()->json([
            'message' => 'Pembayaran '.$payment->code.' berhasil di-void.',
            'data' => new PaymentResource($payment),
        ]);
    }
}
