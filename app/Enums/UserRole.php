<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Keeper = 'keeper';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Keeper => 'Petugas Kandang',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Akses penuh termasuk keuangan, pengguna, dan pengaturan.',
            self::Admin => 'Mengelola inventaris, transaksi, dan laporan operasional.',
            self::Keeper => 'Melihat inventaris, scan, dan cetak barcode.',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Owner => 'badge-purple',
            self::Admin => 'badge-info',
            self::Keeper => 'badge-neutral',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
