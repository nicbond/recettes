<?php

declare(strict_types=1);

namespace App\Enum;

enum ServiceEnum: string
{
    case TECHNIQUE = 'service-technique@test.fr';
    case COMPTABLE = 'service-comptabilite@test.fr';
    case RH = 'ressources-humaines@test.fr';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
