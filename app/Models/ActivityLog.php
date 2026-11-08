<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'login' => 'Login',
            'logout' => 'Logout',
            'create' => 'Tambah data',
            'update' => 'Ubah data',
            'archive' => 'Arsipkan data',
            'void' => 'Void transaksi',
            'payment' => 'Pembayaran',
            'import' => 'Impor data',
            'export' => 'Ekspor data',
            default => ucfirst($this->action),
        };
    }

    public function actionBadge(): string
    {
        return match ($this->action) {
            'create', 'payment' => 'badge-success',
            'update' => 'badge-info',
            'archive', 'void' => 'badge-danger',
            default => 'badge-neutral',
        };
    }
}
