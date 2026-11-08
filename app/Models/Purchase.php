<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'goat_id',
        'price',
        'additional_cost',
        'total',
        'notes',
        'created_by',
        'voided_at',
        'void_reason',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price' => 'decimal:2',
            'additional_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function goat()
    {
        return $this->belongsTo(Goat::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function activePayments(): MorphMany
    {
        return $this->payments()->whereNull('voided_at');
    }

    public function paidAmount(): float
    {
        return (float) $this->activePayments()->sum('amount');
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->total - $this->paidAmount());
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function scopeNotVoided($query)
    {
        return $query->whereNull('voided_at');
    }

    public function scopeVoided($query)
    {
        return $query->whereNotNull('voided_at');
    }
}
