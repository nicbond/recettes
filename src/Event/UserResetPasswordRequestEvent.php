<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User\User;
use App\Event\Traits\FailableTrait;
use App\Notification\FailableEventInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class UserResetPasswordRequestEvent implements FailableEventInterface
{
    use FailableTrait;

    public function __construct(private readonly User $user, private readonly ResetPasswordToken $resetToken)
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
