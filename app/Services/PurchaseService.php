<?php

namespace App\Services;

use App\Enums\GoatGender;
use App\Enums\GoatOrigin;
use App\Enums\GoatStatus;
use App\Enums\PaymentMethod;
use App\Models\Goat;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly PaymentService $payments,
        private readonly ActivityLogger $logger,
    ) {
    }

    /**
     * Create the goat and its purchase record atomically.
     */
    public function create(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $identity = $this->sequences->goatIdentity();

            $goatData = $data['goat'];

            $goat = Goat::query()->create([
                'code' => $identity['code'],
                'barcode' => $identity['barcode'],
                'name' => $goatData['name'],
                'type' => $goatData['type'],
                'gender' => $goatData['gender'],
                'birth_date' => $goatData['birth_date'] ?? null,
                'weight' => $goatData['weight'] ?? 0,
                'color' => $goatData['color'] ?? null,
                'origin' => GoatOrigin::Pembelian->value,
                'mother_id' => null,
                'entry_date' => $data['date'],
                'status' => GoatStatus::Tersedia->value,
                'notes' => $goatData['notes'] ?? null,
            ]);

            $price = round((float) $data['price'], 2);
            $additional = round((float) ($data['additional_cost'] ?? 0), 2);

            $purchase = Purchase::query()->create([
                'code' => $this->sequences->purchaseCode($data['date']),
                'date' => $data['date'],
                'supplier_id' => $data['supplier_id'],
                'goat_id' => $goat->id,
                'price' => $price,
                'additional_cost' => $additional,
                'total' => $price + $additional,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->logger->log('create', $purchase, 'Mencatat pembelian '.$purchase->code.' untuk '.$goat->code, [
                'total' => (float) $purchase->total,
            ]);

            $paid = round((float) ($data['paid_amount'] ?? 0), 2);
            if ($paid > 0) {
                if ($paid > (float) $purchase->total) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Pembayaran awal tidak boleh melebihi total pembelian.',
                    ]);
                }

                $this->payments->record($purchase, [
                    'date' => $data['date'],
                    'amount' => $paid,
                    'method' => $data['payment_method'] ?? PaymentMethod::Tunai->value,
                    'notes' => 'Pembayaran awal pembelian',
                ], $user);
            }

            return $purchase->fresh(['goat', 'supplier']);
        });
    }

    public function void(Purchase $purchase, string $reason, User $user): void
    {
        if ($purchase->isVoided()) {
            throw ValidationException::withMessages([
                'purchase' => 'Pembelian ini sudah di-void.',
            ]);
        }

        DB::transaction(function () use ($purchase, $reason, $user) {
            $goat = $purchase->goat()->withTrashed()->lockForUpdate()->firstOrFail();

            if ($goat->sales()->whereNull('voided_at')->exists()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Kambing dari pembelian ini sudah terjual. Void penjualan terlebih dahulu.',
                ]);
            }

            if ($goat->children()->exists()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Kambing ini tercatat sebagai induk dan tidak dapat di-void.',
                ]);
            }

            foreach ($purchase->activePayments as $payment) {
                $this->payments->void($payment, 'Mengikuti void pembelian '.$purchase->code, $user);
            }

            $purchase->forceFill([
                'voided_at' => now(),
                'void_reason' => $reason,
                'voided_by' => $user->id,
            ])->save();

            $this->logger->log('void', $purchase, 'Void pembelian '.$purchase->code, [
                'reason' => $reason,
            ]);

            $goat->delete();
        });
    }
}
