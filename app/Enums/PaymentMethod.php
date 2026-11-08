<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Tunai = 'tunai';
    case Transfer = 'transfer';
    case Qris = 'qris';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Transfer => 'Transfer',
            self::Qris => 'QRIS',
            self::Lainnya => 'Lainnya',
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
