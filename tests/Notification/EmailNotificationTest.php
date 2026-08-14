<?php

namespace App\Tests\Notification;

use App\DTO\ContactDTO;
use App\Notification\EmailNotification;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

final class EmailNotificationTest extends TestCase
{
    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function testSendEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send');

        $notification = new EmailNotification($mailer);

        $data = new ContactDTO();
        $data->name = 'Nicolas';
        $data->email = 'nicolas@example.com';
        $data->service = 'contact@recettes.fr';
        $data->message = 'Message de test';

        $notification->send($data);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testSendEmailWithNullServiceDoesNothing(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())
            ->method('send');

        $notification = new EmailNotification($mailer);

        $data = new ContactDTO();
        $data->service = null;

        $notification->send($data);
    }
}
