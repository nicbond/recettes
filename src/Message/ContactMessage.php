<?php

namespace App\Message;

use App\DTO\ContactDTO;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async-contact')]
final readonly class ContactMessage
{
    public function __construct(private ContactDTO $contactDTO)
    {
    }

    public function getContactDTO(): ContactDTO
    {
        return $this->contactDTO;
    }
}
