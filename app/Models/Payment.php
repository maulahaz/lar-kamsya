<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'code',
        'date',
        'payable_type',
        'payable_id',
        'amount',
        'method',
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
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'voided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function scopeNotVoided($query)
    {
        return $query->whereNull('voided_at');
    }

    public function direction(): string
    {
        return $this->payable_type === 'sale' ? 'masuk' : 'keluar';
    }
}
