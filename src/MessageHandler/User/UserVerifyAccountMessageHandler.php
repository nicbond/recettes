<?php

namespace App\MessageHandler\User;

use App\Entity\User\User;
use App\Event\UserVerifyRequestEvent;
use App\Message\User\UserVerifyAccountMessage;
use App\Repository\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[AsMessageHandler]
final readonly class UserVerifyAccountMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EventDispatcherInterface $dispatcher,
        private VerifyEmailHelperInterface $verifyEmailHelper,
    ) {
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
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_activate_account',
            (string) $user->getId(),
            (string) $user->getEmail(),
            [
                'id' => $user->getId(),
                'token' => $message->token,
            ]
        );

        $event->setSignatureUrl($signatureComponents->getSignedUrl());
        $this->dispatcher->dispatch($event);

        $this->logger->info(
            'UserVerifyAccountMessage : UserVerifyAccount email sent successfully',
            [
                'email' => $user->getEmail(),
            ]
        );
    }
}
