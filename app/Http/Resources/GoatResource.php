<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new GoatSummaryResource($this->resource))->resolve($request),
            'age_text' => $this->ageText(),
            'notes' => $this->notes,
            'acquisition_cost' => $this->whenLoaded('purchase', fn () => $this->acquisitionCost()),
            'mother' => new GoatSummaryResource($this->whenLoaded('mother')),
            'purchase' => new PurchaseSummaryResource($this->whenLoaded('purchase')),
            'active_sale' => new SaleSummaryResource($this->whenLoaded('activeSale')),
            'sales' => SaleSummaryResource::collection($this->whenLoaded('sales')),
            'children' => GoatSummaryResource::collection($this->whenLoaded('children')),
            'scan_logs' => ScanLogResource::collection($this->whenLoaded('scanLogs')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
