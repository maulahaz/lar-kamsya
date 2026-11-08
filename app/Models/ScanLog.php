<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model
{
    protected $fillable = [
        'user_id',
        'goat_id',
        'barcode',
        'found',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function goat()
    {
        return $this->belongsTo(Goat::class)->withTrashed();
    }
}
