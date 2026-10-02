<?php

namespace App\Controller;

use App\Entity\User\Admin;
use App\Form\User\RegistrationFormType;
use App\Manager\AdminManager;
use App\Repository\User\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, AdminManager $adminManager): Response
    {
        $admin = new Admin();
        $form = $this->createForm(RegistrationFormType::class, $admin, [
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adminManager->createNewAdmin($admin, $form);
            $this->addFlash('success', 'Votre compte a bien été créé. Un e-mail d\'activation vous a été envoyé.');
            $this->addFlash('warning', 'Veuillez par la suite contacter votre administrateur pour vous donner les accès voulus.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/activate', name: 'app_activate_account')]
    public function activate(
        UserRepository $userRepository,
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
    ): Response {
        // We don't need anymore the token in the url with verify-email-bundle
        $user = $userRepository->findOneByIdAndConfirmationTokenNotNull((int) $request->query->get('id'));

        if (!$user) {
            $this->addFlash('danger', 'Compte introuvable ou déjà activé.');

            return $this->redirectToRoute('app_login');
        }

        // The magic of the verify-email bundle: We validate the cryptographic signature of the full link
        try {
            $verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());
        } catch (VerifyEmailExceptionInterface $exception) {
            // If the link has expired (the famous ?expires=...) or if the signature has been changed
            $this->addFlash('danger', 'Ce lien d\'activation est invalide ou a expiré.');

            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        $userRepository->flush();

        $this->addFlash('success', 'Votre compte a bien été activé ! Vous pouvez vous connecter.');

        return $this->render('registration/activate_success.html.twig');
    }
}
