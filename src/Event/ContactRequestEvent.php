<?php

declare(strict_types=1);

namespace App\Event;

use App\Message\ContactMessage;

readonly class ContactRequestEvent
{
    public function __construct(public ContactMessage $message)
    {
    }
}
