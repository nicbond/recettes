<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User\User;
use App\Event\Traits\FailableTrait;

class UserVerifyRequestEvent
{
    use FailableTrait;

    public function __construct(public readonly User $user)
    {
    }
}
