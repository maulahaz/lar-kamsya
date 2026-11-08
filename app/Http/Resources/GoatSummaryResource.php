<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoatSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'type' => $this->type,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'birth_date' => $this->birth_date?->toDateString(),
            'weight' => (float) $this->weight,
            'color' => $this->color,
            'origin' => $this->origin?->value,
            'origin_label' => $this->origin?->label(),
            'mother_id' => $this->mother_id,
            'entry_date' => $this->entry_date?->toDateString(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
