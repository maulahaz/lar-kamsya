<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'farm_name' => $this->farm_name,
            'owner_name' => $this->owner_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'barcode_prefix' => $this->barcode_prefix,
            'low_stock_limit' => (int) $this->low_stock_limit,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
