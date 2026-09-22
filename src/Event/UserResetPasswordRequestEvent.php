<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User\User;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

readonly class UserResetPasswordRequestEvent
{
    public function __construct(private User $user, private ResetPasswordToken $resetToken)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getResetToken(): ResetPasswordToken
    {
        return $this->resetToken;
    }
}
