<?php

namespace App\Enum;

enum Gender: string
{
    case Male = 'M';
    case Female = 'F';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Monsieur',
            self::Female => 'Madame',
        };
    }
}
