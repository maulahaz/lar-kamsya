<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, PaymentService $service)
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'payable_type' => ['required', 'in:purchase,sale'],
            'payable_id' => ['required', 'integer'],
        ]);

        $payable = $data['payable_type'] === 'sale'
            ? Sale::query()->findOrFail($data['payable_id'])
            : Purchase::query()->findOrFail($data['payable_id']);

        try {
            $payment = $service->record($payable, $request->validated(), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Pembayaran '.$payment->code.' berhasil dicatat.');
    }

    public function void(Request $request, Payment $payment, PaymentService $service)
    {
        $this->authorize('void', $payment);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        try {
            $service->void($payment, $reason, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Pembayaran '.$payment->code.' berhasil di-void.');
    }
}
