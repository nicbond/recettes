<?php

namespace App\Controller\Admin;

use App\DTO\CategoryFilterDTO;
use App\Entity\Category;
use App\Form\CategoryFilterType;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/admin/categories', name: 'admin.category.')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly PaginatorInterface $paginator,
        #[Autowire('%number_per_page_category%')]
        private readonly int $numberPerPageCategory,
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $filter = new CategoryFilterDTO();

        $filterForm = $this->createForm(CategoryFilterType::class, $filter);
        $filterForm->handleRequest($request);

        $filters = [];
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var CategoryFilterDTO $data */
            $data = $filterForm->getData();

            if (null !== $data->category) {
                $filters['category'] = $data->category;
            }
        }

        $query = $this->categoryRepository->findAllCategoryByQueryBuilderAndFilter($filters);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $this->numberPerPageCategory
        );

        return $this->render('admin/category/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function new(Request $request): RedirectResponse|Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, [
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category, true);
            $this->addFlash('success', 'La catégorie a bien été créée');

            return $this->redirectToRoute('admin.category.index');
        }

        return $this->render('admin/category/new.html.twig', [
            'form' => $form,
            'show' => false,
        ]);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category, true);
            $this->addFlash('success', 'La catégorie a bien été modifiée');

            return $this->redirectToRoute('admin.category.index');
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
            'show' => false,
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(Request $request, Category $category): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->getString('_token'))) {
            if ($category->getRecipes()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer cette catégorie car elle est liée à des recettes.');

                return $this->redirectToRoute('admin.category.index');
            }

            $this->categoryRepository->remove($category, true);
            $this->addFlash('success', 'Catégorie supprimée avec succès');
        }

        return $this->redirectToRoute('admin.category.index');
    }
}
