<?php

namespace App\MessageHandler\User;

use App\Entity\User\User;
use App\Event\UserVerifyRequestEvent;
use App\Message\User\UserVerifyAccountMessage;
use App\Repository\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UserVerifyAccountMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(UserVerifyAccountMessage $message): void
    {
        $user = $this->userRepository->findOneByConfirmationToken($message->token);
        if (!$user instanceof User) {
            $this->logger->error('UserVerifyAccountMessage : Unable to verify user', [
                'email' => $message->email,
            ]);
            throw new \Exception(sprintf('Unable to verify user %s', $message->email));
        }

        $event = new UserVerifyRequestEvent($user);
        $this->dispatcher->dispatch($event);

        if ($event->isFailed()) {
            $this->logger->error('UserVerifyAccountMessage : Failed to send UserVerifyAccount email');
            throw new \Exception('Failed to send contact message');
        }

        $this->logger->info('UserVerifyAccountMessage : UserVerifyAccount email sent successfully');
    }
}
