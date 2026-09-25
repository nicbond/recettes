<?php

namespace App\EventListener;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessEventListener
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setLastLoginAt(new \DateTime());
        $this->userRepository->save($user, true);
    }
}
