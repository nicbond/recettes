<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ContactRequestEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class LoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContactRequestEvent::class => 'onContactRequestEvent',
        ];
    }

    public function onContactRequestEvent(ContactRequestEvent $event): void
    {
        if ($event->isFailed()) {
            return;
        }

        $this->logger->info('Contact request received', [
            'email' => $event->data->email,
            'subject' => $event->data->service,
        ]);
    }
}
