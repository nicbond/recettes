<?php

declare(strict_types=1);

namespace App\Notification;

readonly class NotificationFactory
{
    public function __construct(
        private EmailNotification $email,
        private SmsNotification $sms,
    ) {
    }

    public function create(string $type): ContactNotificationInterface
    {
        return match ($type) {
            'email' => $this->email,
            'sms' => $this->sms,
            default => throw new \InvalidArgumentException(sprintf('Type de notification "%s" non supporté.', $type)),
        };
    }
}
