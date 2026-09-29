<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User\Admin;

final class AdminFilterDTO
{
    public ?Admin $email = null;
}
