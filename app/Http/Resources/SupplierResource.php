<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'notes' => $this->notes,
            'purchases_count' => $this->when(isset($this->purchases_count), fn () => (int) $this->purchases_count),
            'purchases_sum_total' => $this->when(isset($this->purchases_sum_total), fn () => (float) $this->purchases_sum_total),
            'recent_purchases' => PurchaseSummaryResource::collection($this->whenLoaded('purchases')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
