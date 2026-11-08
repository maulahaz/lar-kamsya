<?php

namespace App\Services;

use App\Models\Sequence;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Allocate the next number for a sequence key. Must run inside a database
     * transaction so the row lock is held until the record is written.
     */
    public function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $sequence = Sequence::query()->where('key', $key)->lockForUpdate()->first();

            if (! $sequence) {
                Sequence::query()->insertOrIgnore([
                    'key' => $key,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = Sequence::query()->where('key', $key)->lockForUpdate()->firstOrFail();
            }

            $sequence->last_number++;
            $sequence->save();

            return $sequence->last_number;
        });
    }

    /**
     * @return array{code: string, barcode: string, number: int}
     */
    public function goatIdentity(): array
    {
        $number = $this->next('goat');
        $prefix = Setting::current()->barcode_prefix ?: '8991';

        return [
            'number' => $number,
            'code' => 'KMB-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'barcode' => $prefix.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
        ];
    }

    public function supplierCode(): string
    {
        return 'SUP-'.str_pad((string) $this->next('supplier'), 3, '0', STR_PAD_LEFT);
    }

    public function customerCode(): string
    {
        return 'CUS-'.str_pad((string) $this->next('customer'), 3, '0', STR_PAD_LEFT);
    }

    public function purchaseCode(Carbon|string $date): string
    {
        $year = Carbon::parse($date)->year;
        $number = $this->next('purchase:'.$year);

        return 'TRX-B-'.$year.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function saleCode(Carbon|string $date): string
    {
        $year = Carbon::parse($date)->year;
        $number = $this->next('sale:'.$year);

        return 'TRX-J-'.$year.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function paymentCode(Carbon|string $date): string
    {
        $year = Carbon::parse($date)->year;
        $number = $this->next('payment:'.$year);

        return 'PAY-'.$year.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function expenseCode(Carbon|string $date): string
    {
        $year = Carbon::parse($date)->year;
        $number = $this->next('expense:'.$year);

        return 'BBN-'.$year.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Set the sequence counters to the given values (used by seeders/imports).
     *
     * @param  array<string, int>  $values
     */
    public function sync(array $values): void
    {
        foreach ($values as $key => $number) {
            Sequence::query()->updateOrCreate(['key' => $key], ['last_number' => $number]);
        }
    }
}
