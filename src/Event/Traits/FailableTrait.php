<?php

declare(strict_types=1);

namespace App\Event\Traits;

trait FailableTrait
{
    private bool $failed = false;

    public function setFailed(bool $failed): void
    {
        $this->failed = $failed;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }
}
