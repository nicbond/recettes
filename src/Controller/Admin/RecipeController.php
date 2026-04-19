<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/admin/recettes', name: 'admin.recipe.')]
final class RecipeController extends AbstractController
{
    public function __construct(
        private readonly RecipeRepository $recipeRepository,
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $recipes = $this->recipeRepository->findWithDurationLowerThan(30);

        return $this->render('admin/recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recettes/{slug}-{id}', name: 'recipe.show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9-]+'])]
    public function show(string $slug, Recipe $recipe): Response
    {
        $recipe = $this->recipeRepository->find($recipe->getId());
        $form = $this->createForm(RecipeType::class, $recipe, ['disabled' => true]);

        if ($recipe->getSlug() != $slug) {
            return $this->redirectToRoute('recipe.show', [
                'slug' => $recipe->getSlug(),
                'id' => $recipe->getId(),
                'form' => $form->createView(),
            ]);
        }

        return $this->render('recipe/edit.html.twig', [
            'recipe' => $recipe,
            'show' => true,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): RedirectResponse|Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recipeRepository->save($recipe, true);
            $this->addFlash('success', 'La recette a bien été créée');

            return $this->redirectToRoute('admin.recipe.index');
        }

        return $this->render('admin/recipe/new.html.twig', [
            'form' => $form,
            'show' => false,
        ]);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function edit(Recipe $recipe, Request $request): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recipeRepository->save($recipe, true);
            $this->addFlash('success', 'La recette a bien été modifiée');

            return $this->redirectToRoute('admin.recipe.index');
        }

        return $this->render('admin/recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form->createView(),
            'show' => false,
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(Request $request, Recipe $recipe): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->getString('_token'))) {
            $this->recipeRepository->remove($recipe, true);
            $this->addFlash('success', 'Recette supprimée avec succès');
        }

        return $this->redirectToRoute('admin.recipe.index');
    }
}
