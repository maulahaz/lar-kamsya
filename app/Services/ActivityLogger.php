<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): ActivityLog
    {
        $label = null;

        if ($subject) {
            $label = $subject->getAttribute('code')
                ?? $subject->getAttribute('name')
                ?? $subject->getAttribute('email');
        }

        return ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $label,
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
