<?php

declare(strict_types=1);

namespace App\Message\User;

use Symfony\Component\Messenger\Attribute\AsMessage;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

#[AsMessage('async-user-reset-password')]
final readonly class UserResetPasswordMessage
{
    public function __construct(
        public int $userId,
        public ResetPasswordToken $resetToken,
    ) {
    }
}
