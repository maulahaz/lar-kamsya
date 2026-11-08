<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'farm_name',
        'owner_name',
        'phone',
        'address',
        'barcode_prefix',
        'low_stock_limit',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_limit' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'farm_name' => 'Kamsya Farm',
            'owner_name' => 'Admin Kamsya',
            'phone' => '0812-9000-1234',
            'address' => 'Jl. Raya Peternakan No. 18, Bandung',
            'barcode_prefix' => '8991',
            'low_stock_limit' => 5,
        ]);
    }
}
