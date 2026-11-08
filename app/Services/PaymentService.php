<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function record(Model $payable, array $data, User $user): Payment
    {
        if (! $payable instanceof Purchase && ! $payable instanceof Sale) {
            throw ValidationException::withMessages(['payment' => 'Transaksi pembayaran tidak dikenali.']);
        }

        if ($payable->isVoided()) {
            throw ValidationException::withMessages(['payment' => 'Transaksi sudah di-void dan tidak dapat dibayar.']);
        }

        $amount = round((float) $data['amount'], 2);
        $remaining = round($payable->remainingAmount(), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari nol.']);
        }

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal melebihi sisa tagihan ('.number_format($remaining, 0, ',', '.').').',
            ]);
        }

        return DB::transaction(function () use ($payable, $data, $user, $amount) {
            $payment = Payment::query()->create([
                'code' => $this->sequences->paymentCode($data['date']),
                'date' => $data['date'],
                'payable_type' => $payable instanceof Sale ? 'sale' : 'purchase',
                'payable_id' => $payable->getKey(),
                'amount' => $amount,
                'method' => $data['method'] ?? PaymentMethod::Tunai->value,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->logger->log('payment', $payment, 'Pembayaran '.$payment->code.' untuk '.$payable->code, [
                'amount' => $amount,
            ]);

            return $payment;
        });
    }

    public function void(Payment $payment, string $reason, User $user): void
    {
        if ($payment->isVoided()) {
            throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah di-void.']);
        }

        $payment->forceFill([
            'voided_at' => now(),
            'void_reason' => $reason,
            'voided_by' => $user->id,
        ])->save();

        $this->logger->log('void', $payment, 'Void pembayaran '.$payment->code, [
            'reason' => $reason,
        ]);
    }
}
