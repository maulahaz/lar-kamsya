<?php

namespace App\Enums;

enum GoatOrigin: string
{
    case Pembelian = 'pembelian';
    case Peranakan = 'peranakan';

    public function label(): string
    {
        return match ($this) {
            self::Pembelian => 'Pembelian',
            self::Peranakan => 'Peranakan',
        };
    }
}
