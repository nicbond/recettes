<?php

namespace App\MessageHandler;

use App\Event\ContactRequestEvent;
use App\Message\ContactMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ContactMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ContactMessage $message): void
    {
        $event = new ContactRequestEvent($message);
        $this->dispatcher->dispatch($event);
        if ($event->isFailed()) {
            $this->logger->error('ContactMessage : Failed to send contact message');
            throw new \Exception('Failed to send contact message');
        }

        $this->logger->info('ContactMessage : Contact message sent successfully');
    }
}
