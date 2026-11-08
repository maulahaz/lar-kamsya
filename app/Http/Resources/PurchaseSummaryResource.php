<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();
        $total = (float) $this->total;

        if (array_key_exists('paid_total', $attributes)) {
            $paid = (float) ($attributes['paid_total'] ?? 0);
        } elseif ($this->relationLoaded('payments')) {
            $paid = (float) $this->payments->whereNull('voided_at')->sum('amount');
        } elseif ($this->relationLoaded('activePayments')) {
            $paid = (float) $this->activePayments->sum('amount');
        } else {
            $paid = $this->paidAmount();
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'date' => $this->date?->toDateString(),
            'supplier_id' => $this->supplier_id,
            'goat_id' => $this->goat_id,
            'price' => (float) $this->price,
            'additional_cost' => (float) $this->additional_cost,
            'total' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $total - $paid),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'void_reason' => $this->void_reason,
        ];
    }
}
