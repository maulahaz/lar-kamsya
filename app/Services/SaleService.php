<?php

namespace App\Services;

use App\Enums\GoatStatus;
use App\Enums\PaymentMethod;
use App\Models\Goat;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly PaymentService $payments,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function create(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $goat = Goat::query()->lockForUpdate()->findOrFail($data['goat_id']);

            if ($goat->status !== GoatStatus::Tersedia) {
                throw ValidationException::withMessages([
                    'goat_id' => 'Kambing ini sudah terjual dan tidak dapat dijual kembali.',
                ]);
            }

            if ($goat->sales()->whereNull('voided_at')->exists()) {
                throw ValidationException::withMessages([
                    'goat_id' => 'Kambing ini masih memiliki penjualan aktif.',
                ]);
            }

            $price = round((float) $data['price'], 2);
            $costPrice = round($goat->acquisitionCost(), 2);

            $sale = Sale::query()->create([
                'code' => $this->sequences->saleCode($data['date']),
                'date' => $data['date'],
                'goat_id' => $goat->id,
                'customer_id' => $data['customer_id'],
                'price' => $price,
                'cost_price' => $costPrice,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $paid = round((float) ($data['paid_amount'] ?? 0), 2);
            if ($paid > 0) {
                if ($paid > $price) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Pembayaran awal tidak boleh melebihi harga jual.',
                    ]);
                }

                $this->payments->record($sale, [
                    'date' => $data['date'],
                    'amount' => $paid,
                    'method' => $data['payment_method'] ?? PaymentMethod::Tunai->value,
                    'notes' => 'Pembayaran awal penjualan',
                ], $user);
            }

            $goat->status = GoatStatus::Terjual;
            $goat->save();

            $this->logger->log('create', $sale, 'Mencatat penjualan '.$sale->code.' untuk '.$goat->code, [
                'price' => $price,
                'cost_price' => $costPrice,
            ]);

            return $sale->fresh(['goat', 'customer']);
        });
    }

    public function void(Sale $sale, string $reason, User $user): void
    {
        if ($sale->isVoided()) {
            throw ValidationException::withMessages([
                'sale' => 'Penjualan ini sudah di-void.',
            ]);
        }

        DB::transaction(function () use ($sale, $reason, $user) {
            $goat = $sale->goat()->withTrashed()->lockForUpdate()->firstOrFail();

            foreach ($sale->activePayments as $payment) {
                $this->payments->void($payment, 'Mengikuti void penjualan '.$sale->code, $user);
            }

            $sale->forceFill([
                'voided_at' => now(),
                'void_reason' => $reason,
                'voided_by' => $user->id,
            ])->save();

            if ($sale->goat_id === $goat->id && ! $goat->trashed()) {
                $hasOther = $goat->sales()
                    ->whereNull('voided_at')
                    ->whereKeyNot($sale->id)
                    ->exists();

                if (! $hasOther) {
                    $goat->status = GoatStatus::Tersedia;
                    $goat->save();
                }
            }

            $this->logger->log('void', $sale, 'Void penjualan '.$sale->code, [
                'reason' => $reason,
            ]);
        });
    }
}
