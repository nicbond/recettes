<?php

declare(strict_types=1);

namespace App\Notification;

use App\DTO\ContactDTO;

class SmsNotification implements ContactNotificationInterface
{
    public function send(ContactDTO $data): void
    {
        // sms logic to implement
    }
}
