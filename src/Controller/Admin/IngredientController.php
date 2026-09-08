<?php

namespace App\Controller\Admin;

use App\Entity\Recipe\Ingredient;
use App\Repository\Recipe\IngredientRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IngredientController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     */
    #[Route('/ingredient/create-ajax', name: 'ingredient_create_ajax', methods: ['POST'])]
    public function createAjax(
        Request $request,
        IngredientRepository $ingredientRepositoryRepository,
    ): JsonResponse {
        $name = trim($request->request->getString('name'));

        if (empty($name)) {
            return new JsonResponse(['error' => 'Le nom est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $ingredientRepositoryRepository->findOneByNameInsensitive($name);
        if ($existing) {
            return new JsonResponse([
                'value' => $existing->getId(),
                'text' => $existing->getName(),
            ]);
        }

        $ingredient = new Ingredient();
        $ingredient->setName($name);
        $ingredientRepositoryRepository->save($ingredient, true);

        return new JsonResponse([
            'value' => $ingredient->getId(),
            'text' => $ingredient->getName(),
        ], Response::HTTP_CREATED);
    }
}
