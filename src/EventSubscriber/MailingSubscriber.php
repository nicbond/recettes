<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User\User;
use App\Event\ContactRequestEvent;
use App\Event\UserResetPasswordRequestEvent;
use App\Event\UserVerifyRequestEvent;
use App\Notification\FailableEventInterface;
use App\Notification\NotificationFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class MailingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationFactory $notificationFactory,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContactRequestEvent::class => 'onContactRequestEvent',
            UserVerifyRequestEvent::class => 'onUserVerifyRequestEvent',
            UserResetPasswordRequestEvent::class => 'onUserResetPasswordRequestEvent',
        ];
    }

    public function onContactRequestEvent(ContactRequestEvent $event): void
    {
        try {
            $this->notificationFactory
                ->create('email')
                ->send($event->message->getContactDTO());
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
                'email' => $event->message->getContactDTO()->email,
            ]);

            $event->setFailed(true);
        }
    }

    public function onUserVerifyRequestEvent(UserVerifyRequestEvent $event): void
    {
        $this->sendUserEmail(
            $event->user,
            'Confirmation de votre compte',
            'emails/user/user_account_confirmation.html.twig',
            ['signedUrl' => $event->getSignatureUrl()],
            $event
        );
    }

    public function onUserResetPasswordRequestEvent(UserResetPasswordRequestEvent $event): void
    {
        $this->sendUserEmail(
            $event->getUser(),
            'Demande de réinitialisation de mot de passe',
            'reset_password/email.html.twig',
            ['resetToken' => $event->getResetToken()],
            $event
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendUserEmail(
        User $user,
        string $subject,
        string $template,
        array $context,
        FailableEventInterface $event,
    ): void {
        try {
            $this->notificationFactory
                ->createForUser()
                ->sendToUser($user, $subject, $template, $context);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
                'email' => $user->getEmail(),
            ]);
            $event->setFailed(true);
        }
    }
}
