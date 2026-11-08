<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'address',
        'notes',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function hasActiveTransactions(): bool
    {
        return $this->sales()->whereNull('voided_at')->exists();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        return collect($parts)
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
