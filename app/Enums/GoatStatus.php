<?php

namespace App\Enums;

enum GoatStatus: string
{
    case Tersedia = 'tersedia';
    case Terjual = 'terjual';

    public function label(): string
    {
        return match ($this) {
            self::Tersedia => 'Tersedia',
            self::Terjual => 'Terjual',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Tersedia => 'badge-success',
            self::Terjual => 'badge-danger',
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
