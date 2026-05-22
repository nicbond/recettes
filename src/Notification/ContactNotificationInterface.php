<?php

declare(strict_types=1);

namespace App\Notification;

use App\DTO\ContactDTO;

interface ContactNotificationInterface
{
    public function send(ContactDTO $data): void;
}
