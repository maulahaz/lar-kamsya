<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Pakan = 'pakan';
    case Obat = 'obat';
    case TenagaKerja = 'tenaga_kerja';
    case Transportasi = 'transportasi';
    case Listrik = 'listrik';
    case Peralatan = 'peralatan';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Pakan => 'Pakan & Konsentrat',
            self::Obat => 'Obat & Vitamin',
            self::TenagaKerja => 'Tenaga Kerja',
            self::Transportasi => 'Transportasi',
            self::Listrik => 'Listrik & Air',
            self::Peralatan => 'Peralatan Kandang',
            self::Lainnya => 'Lain-lain',
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
