<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User\Admin;
use App\Form\User\AdminCreateFormType;
use App\Manager\AdminManager;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/admins', name: 'admin.admins.')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly AdminManager $adminManager,
    ) {
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): RedirectResponse|Response
    {
        $admin = new Admin();
        $form = $this->createForm(AdminCreateFormType::class, $admin, [
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->adminManager->createNewAdmin($admin, $form);
            $this->addFlash('success', 'Le nouveau compte admin a été créé. Un e-mail d\'activation a été envoyé.');

            return $this->redirectToRoute('admin.permissions.index');
        }

        return $this->render('admin/permissions/admin/new-admin.html.twig', [
            'form' => $form,
            'show' => false,
        ]);
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    #[Route('/{id}/verified-account', name: 'verified-account', methods: ['POST'])]
    public function verifiedAccount(Request $request, Admin $admin): RedirectResponse|Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('resend-activation-'.$admin->getId(), $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Action annulée.');

            return $this->redirectToRoute('admin.permissions.index');
        }

        if ($admin->isVerified()) {
            $this->addFlash('warning', 'Ce compte administrateur est déjà vérifié.');

            return $this->redirectToRoute('admin.permissions.index');
        }

        $this->adminManager->resendVerifiedAccountEmail($admin);
        $this->addFlash('success', 'L\'e-mail de vérification du compte a été renvoyé.');

        return $this->redirectToRoute('admin.permissions.index');
    }
}
