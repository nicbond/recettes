<?php

declare(strict_types=1);

namespace App\MessageHandler\User;

use App\Entity\User\User;
use App\Event\UserResetPasswordRequestEvent;
use App\Message\User\UserResetPasswordMessage;
use App\Repository\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UserResetPasswordMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(UserResetPasswordMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);

        if (!$user instanceof User) {
            $this->logger->error(
                'UserResetPasswordMessage : Unable to find user',
                [
                    'userId' => $message->userId,
                ]
            );

            throw new \RuntimeException(sprintf('Unable to reset password user %s', $message->userId));
        }

        $event = new UserResetPasswordRequestEvent($user, $message->resetToken);
        $this->dispatcher->dispatch($event);

        $this->logger->info(
            'UserResetPasswordMessage : UserResetPassword email sent successfully',
            [
                'userId' => $message->userId,
                'email' => $user->getEmail(),
            ]
        );
    }
}
