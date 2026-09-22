<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User\User;

final class UserVerifyRequestEvent
{
    private ?string $signatureUrl = null;

    public function __construct(
        public readonly User $user,
    ) {
    }

    public function setSignatureUrl(string $signatureUrl): void
    {
        $this->signatureUrl = $signatureUrl;
    }

    public function getSignatureUrl(): ?string
    {
        return $this->signatureUrl;
    }
}
