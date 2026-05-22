<?php

declare(strict_types=1);

namespace App\Event;

use App\DTO\ContactDTO;

class ContactRequestEvent
{
    private bool $failed = false;

    public function __construct(public readonly ContactDTO $data)
    {
    }

    public function setFailed(bool $failed): void
    {
        $this->failed = $failed;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }
}
