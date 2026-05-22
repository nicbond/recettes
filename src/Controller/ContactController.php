<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Event\ContactRequestEvent;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, EventDispatcherInterface $dispatcher): Response
    {
        $data = new ContactDTO();
        $form = $this->createForm(ContactType::class, $data, [
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $data->service) {
                throw new \LogicException('Service ne peut pas être null après validation');
            }

            $event = new ContactRequestEvent($data);
            $dispatcher->dispatch($event);

            $this->addFlash(
                $event->isFailed() ? 'danger' : 'success',
                $event->isFailed() ? "Impossible d'envoyer votre message" : 'Votre message a bien été envoyé'
            );

            return $this->redirectToRoute('contact');
        }

        return $this->render('contact/contact.html.twig', [
            'form' => $form,
            'show' => false,
        ]);
    }
}
