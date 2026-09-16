<?php

declare(strict_types=1);

namespace App\Notification;

use App\DTO\ContactDTO;
use App\Entity\User\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Exception\LogicException;

readonly class EmailNotification implements ContactNotificationInterface, UserNotificationInterface
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

    /**
     * @throws TransportExceptionInterface
     */
    public function sendToUser(User $user, string $subject, string $template, array $context = []): void
    {
        $emailAddress = $user->getEmail();

        if (null === $emailAddress || '' === $emailAddress) {
            throw new LogicException(sprintf('Impossible d\'envoyer un e-mail à l\'utilisateur #%d car son adresse email est manquante.', $user->getId() ?? 0));
        }

        $this->mailer->send(
            (new TemplatedEmail())
                ->to($emailAddress)
                ->from('noreply@recettes.fr')
                ->subject($subject)
                ->htmlTemplate($template)
                ->context(array_merge(['user' => $user], $context))
        );
    }
}
