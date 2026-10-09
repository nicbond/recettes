<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ApiClient implements UserInterface
{
    public function __construct(private string $identifier)
    {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('API client identifier cannot be empty.');
        }
    }

    public function getRoles(): array
    {
        return ['ROLE_API'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        assert('' !== $this->identifier);

        return $this->identifier;
    }
}
