<?php

declare(strict_types=1);

namespace App\Enum;

enum Role: string
{
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    case ADMIN = 'ROLE_ADMIN';

    case USER = 'ROLE_USER';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
