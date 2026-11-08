<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => $this->actionLabel(),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->subject_label,
            'description' => $this->description,
            'properties' => $this->properties,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
