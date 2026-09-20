<?php

namespace App\MessageHandler\User;

use App\Entity\User\User;
use App\Event\UserResetPasswordRequestEvent;
use App\Message\User\UserResetPasswordMessage;
use App\Repository\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsMessageHandler]
final readonly class UserResetPasswordMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EventDispatcherInterface $dispatcher,
        private ResetPasswordHelperInterface $resetPasswordHelper)
    {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(UserResetPasswordMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);

        if (!$user instanceof User) {
            $this->logger->error(
                'UserResetPasswordMessage : Unable to find user',
                [
                    'user' => $message->userId,
                ]
            );

            throw new \Exception(sprintf('Unable to reset password user %s', $message->userId));
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->logger->error(
                'Error generateResetToken : Unable to generate reset token',
                [
                    'reason' => $e->getReason(),
                    'message' => $e->getMessage(),
                    'userId' => $user->getId(),
                ]
            );

            return;
        }

        $event = new UserResetPasswordRequestEvent($user, $resetToken);
        $this->dispatcher->dispatch($event);

        if ($event->isFailed()) {
            $this->logger->error('UserResetPasswordMessage : Failed to send UserResetPassword email');
            throw new \Exception('Failed to send contact message');
        }

        $this->logger->info('UserResetPasswordMessage : UserResetPassword email sent successfully');
    }
}
