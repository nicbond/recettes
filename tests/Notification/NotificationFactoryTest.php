<?php

namespace App\Tests\Notification;

use App\Notification\ContactNotificationInterface;
use App\Notification\EmailNotification;
use App\Notification\NotificationFactory;
use App\Notification\SmsNotification;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

final class NotificationFactoryTest extends TestCase
{
    private NotificationFactory $factory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->factory = new NotificationFactory(
            email: $this->createMock(EmailNotification::class),
            sms: $this->createMock(SmsNotification::class),
        );
    }

    public function testCreateEmailNotification(): void
    {
        $notification = $this->factory->create('email');

        self::assertInstanceOf(ContactNotificationInterface::class, $notification);
        self::assertInstanceOf(EmailNotification::class, $notification);
    }

    public function testCreateSmsNotification(): void
    {
        $notification = $this->factory->create('sms');

        self::assertInstanceOf(ContactNotificationInterface::class, $notification);
        self::assertInstanceOf(SmsNotification::class, $notification);
    }

    public function testCreateWithInvalidTypeThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Type de notification "push" non supporté.');

        $this->factory->create('push');
    }
}
