<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User\Admin;
use App\Form\User\AdminPermissionsType;
use App\Repository\User\AdminRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/permissions', name: 'admin.permissions.')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class AdminPermissionsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(AdminRepository $adminRepository): Response
    {
        return $this->render('admin/permissions/index.html.twig', [
            'admins' => $adminRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Admin $admin, Request $request, AdminRepository $adminRepository): Response
    {
        if ($admin->isSuperAdmin()) {
            $this->addFlash('danger', 'Impossible de modifier les permissions d\'un Super Admin.');

            return $this->redirectToRoute('admin.permissions.index');
        }

        $form = $this->createForm(AdminPermissionsType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adminRepository->save($admin, true);
            $this->addFlash('success', 'Permissions mises à jour avec succès.');

            return $this->redirectToRoute('admin.permissions.index');
        }

        return $this->render('admin/permissions/edit.html.twig', [
            'admin' => $admin,
            'form' => $form,
        ]);
    }
}
