<?php

declare(strict_types=1);

namespace App\Notification;

interface FailableEventInterface
{
    public function setFailed(bool $failed): void;

    public function isFailed(): bool;
}
