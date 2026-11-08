<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'date' => $this->date?->toDateString(),
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'payable_code' => $this->whenLoaded('payable', fn () => $this->payable?->code),
            'direction' => $this->direction(),
            'amount' => (float) $this->amount,
            'method' => $this->method?->value,
            'method_label' => $this->method?->label(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'voided_by' => $this->voided_by,
            'voided_at' => $this->voided_at?->toIso8601String(),
            'void_reason' => $this->void_reason,
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
