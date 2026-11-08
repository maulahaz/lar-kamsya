<?php

namespace App\Enums;

enum GoatGender: string
{
    case Jantan = 'jantan';
    case Betina = 'betina';

    public function label(): string
    {
        return match ($this) {
            self::Jantan => 'Jantan',
            self::Betina => 'Betina',
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
