<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScanLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'found' => (bool) $this->found,
            'goat_id' => $this->goat_id,
            'goat' => new GoatSummaryResource($this->whenLoaded('goat')),
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
