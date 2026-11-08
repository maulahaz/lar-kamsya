<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attributes = $this->resource->getAttributes();
        $price = (float) $this->price;

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
            'goat_id' => $this->goat_id,
            'customer_id' => $this->customer_id,
            'price' => $price,
            'cost_price' => (float) $this->cost_price,
            'profit' => $this->profit(),
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $price - $paid),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'void_reason' => $this->void_reason,
        ];
    }
}
