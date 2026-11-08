<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new PurchaseSummaryResource($this->resource))->resolve($request),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'voided_by' => $this->voided_by,
            'supplier' => new SupplierSummaryResource($this->whenLoaded('supplier')),
            'goat' => new GoatSummaryResource($this->whenLoaded('goat')),
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
