<?php

namespace App\Security;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class UserChecker implements UserCheckerInterface
{
    public function __construct()
    {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException("Votre compte n'est pas encore activé.\nVeuillez cliquer sur le lien envoyé par e-mail.");
        }
    }

    /**
     * This method is mandatory in the interface,
     * but it is left empty so that the EventListener takes over after 2FA.
     */
    public function checkPostAuth(UserInterface $user): void
    {
    }
}
