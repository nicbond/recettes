<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminFilterDTO;
use App\Entity\User\Admin;
use App\Form\User\AdminFilterType;
use App\Form\User\AdminPermissionsType;
use App\Repository\User\AdminRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/permissions', name: 'admin.permissions.')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class AdminPermissionsController extends AbstractController
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly PaginatorInterface $paginator,
        #[Autowire('%number_per_page_admin%')]
        private readonly int $numberPerPageAdmin,
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $filter = new AdminFilterDTO();

        $filterForm = $this->createForm(AdminFilterType::class, $filter);
        $filterForm->handleRequest($request);

        $filters = [];
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var AdminFilterDTO $data */
            $data = $filterForm->getData();
            $email = $data->email?->getEmail();

            if (null !== $email) {
                $filters['email'] = $email;
            }
        }

        $query = $this->adminRepository->findAllAdmins($filters);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $this->numberPerPageAdmin
        );

        return $this->render('admin/permissions/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm,
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
