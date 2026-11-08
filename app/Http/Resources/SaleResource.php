<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new SaleSummaryResource($this->resource))->resolve($request),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'voided_by' => $this->voided_by,
            'goat' => new GoatSummaryResource($this->whenLoaded('goat')),
            'customer' => new CustomerSummaryResource($this->whenLoaded('customer')),
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
