<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ContactRequestEvent;
use App\Event\UserVerifyRequestEvent;
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
        try {
            $this->notificationFactory
                ->createForUser()
                ->sendToUser(
                    $event->user,
                    'Confirmation de votre compte',
                    'emails/user/user_account_confirmation.html.twig',
                );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
                'email' => $event->user->getEmail(),
            ]);

            $event->setFailed(true);
        }
    }
}
