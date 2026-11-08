<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'date' => $this->date?->toDateString(),
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'amount' => (float) $this->amount,
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
