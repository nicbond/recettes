<?php

declare(strict_types=1);

namespace App\Notification;

use App\DTO\ContactDTO;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

readonly class EmailNotification implements ContactNotificationInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(ContactDTO $data): void
    {
        if (null === $data->service) {
            return;
        }

        $this->mailer->send(
            (new TemplatedEmail())
                ->to($data->service)
                ->from($data->email)
                ->subject('Demande de contact')
                ->htmlTemplate('emails/contact.html.twig')
                ->context(['data' => $data])
        );
    }
}
