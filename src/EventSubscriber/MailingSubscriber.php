<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ContactRequestEvent;
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
        ];
    }

    public function onContactRequestEvent(ContactRequestEvent $event): void
    {
        try {
            $this->notificationFactory
                ->create('email')
                ->send($event->data);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
                'email' => $event->data->email,
            ]);

            $event->setFailed(true);
        }
    }
}
