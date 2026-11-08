<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    protected $fillable = [
        'code',
        'date',
        'goat_id',
        'customer_id',
        'price',
        'cost_price',
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
            'cost_price' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function goat()
    {
        return $this->belongsTo(Goat::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
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
        return max(0, (float) $this->price - $this->paidAmount());
    }

    public function profit(): float
    {
        return (float) $this->price - (float) $this->cost_price;
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
