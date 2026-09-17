<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User\Admin;
use App\Notification\EmailNotification;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

final class EmailNotificationControllerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private EmailNotification $emailNotification;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // With createMock, we verify how many times the send() method is called.
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->emailNotification = new EmailNotification($this->mailer);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendToUserSendsEmail(): void
    {
        $user = new Admin();
        $user->setEmail('admin@test.com');

        $this->mailer
            ->expects(self::once())
            ->method('send');

        $this->emailNotification->sendToUser(
            $user,
            'Activation de votre compte',
            'emails/activation.html.twig',
            ['token' => 'abc123']
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendToUserThrowsExceptionWhenEmailIsNull(): void
    {
        $user = new Admin();

        $this->mailer
            ->expects(self::never())
            ->method('send');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/adresse email est manquante/');

        $this->emailNotification->sendToUser(
            $user,
            'Activation de votre compte',
            'emails/activation.html.twig'
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendToUserThrowsExceptionWhenEmailIsEmpty(): void
    {
        $user = new Admin();
        $user->setEmail('');

        $this->mailer
            ->expects(self::never())
            ->method('send');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/adresse email est manquante/');

        $this->emailNotification->sendToUser(
            $user,
            'Activation de votre compte',
            'emails/activation.html.twig'
        );
    }
}
