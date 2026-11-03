<?php

declare(strict_types=1);

namespace App\Message\User;

use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Uid\Uuid;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

#[AsMessage('async-user-reset-password')]
final readonly class UserResetPasswordMessage
{
    public function __construct(
        public Uuid $userId,
        public ResetPasswordToken $resetToken,
    ) {
    }
}
