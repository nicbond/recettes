<?php

namespace App\Message\User;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async-user-account-verify')]
final readonly class UserVerifyAccountMessage
{
    public function __construct(public string $email, public string $token)
    {
    }
}
