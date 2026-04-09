<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\ContactType;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $data = new ContactDTO();
        $form = $this->createForm(ContactType::class, $data, [
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mail = (new TemplatedEmail())
                ->to($data->service)
                ->from($data->email)
                ->subject('Demande de contact')
                ->htmlTemplate('emails/contact.html.twig')
                ->context(['data' => $data])
            ;

            try {
                $mailer->send($mail);
                $this->addFlash('success', 'Votre email a bien été envoyé');

                return $this->redirectToRoute('contact');
            } catch (\Throwable $e) {
                $logger->error('Failed to send email', [
                    'status' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'email' => $data->email,
                ]);

                $this->addFlash('danger', "Impossible d'envoyer votre email");

                return $this->redirectToRoute('contact');
            }
        }

        return $this->render('contact/contact.html.twig', [
            'form' => $form,
            'show' => false,
        ]);
    }
}
