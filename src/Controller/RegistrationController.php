<?php

namespace App\Controller;

use App\Entity\User\Admin;
use App\Form\User\RegistrationFormType;
use App\Message\User\UserVerifyAccountMessage;
use App\Repository\User\UserRepository;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository, MessageBusInterface $bus): Response
    {
        $user = new Admin();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            assert(is_string($plainPassword));

            $token = bin2hex(random_bytes(32));
            $user
                ->setConfirmationToken($token)
                ->setIsVerified(false)
                ->setPassword($userPasswordHasher->hashPassword($user, $plainPassword)
                );

            $email = $user->getEmail();
            $token = $user->getConfirmationToken();

            if (null === $email || null === $token) {
                throw new \LogicException('User email and confirmation token must not be null.');
            }

            $bus->dispatch(new UserVerifyAccountMessage($email, $token));
            $userRepository->save($user, true);
            $this->addFlash('success', 'Votre compte a bien été créé. Un e-mail d\'activation vous a été envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/activate/{token}', name: 'app_activate_account')]
    public function activate(string $token, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Ce lien d\'activation est invalide ou a expiré.');

            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        $userRepository->flush();

        return $this->render('registration/activate_success.html.twig');
    }
}
