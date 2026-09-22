<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User\User;
use App\Event\ContactRequestEvent;
use App\Event\UserResetPasswordRequestEvent;
use App\Event\UserVerifyRequestEvent;
use App\Notification\NotificationFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class MailingSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationFactory $notificationFactory)
    {
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
        $this->notificationFactory
            ->create('email')
            ->send($event->message->getContactDTO())
        ;
    }

    public function onUserVerifyRequestEvent(UserVerifyRequestEvent $event): void
    {
        $this->sendUserEmail(
            $event->user,
            'Confirmation de votre compte',
            'emails/user/user_account_confirmation.html.twig',
            ['signedUrl' => $event->getSignatureUrl()]
        );
    }

    public function onUserResetPasswordRequestEvent(UserResetPasswordRequestEvent $event): void
    {
        $this->sendUserEmail(
            $event->getUser(),
            'Demande de réinitialisation de mot de passe',
            'reset_password/email.html.twig',
            ['resetToken' => $event->getResetToken()]
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendUserEmail(User $user, string $subject, string $template, array $context): void
    {
        $this->notificationFactory
            ->createForUser()
            ->sendToUser($user, $subject, $template, $context);
    }
}
