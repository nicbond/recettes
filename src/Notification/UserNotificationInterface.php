<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\User\User;

interface UserNotificationInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function sendToUser(User $user, string $subject, string $template, array $context = []): void;
}
