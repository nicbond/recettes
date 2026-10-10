<?php

declare(strict_types=1);

namespace App\State\Recipe;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Recipe\Recipe;
use App\Repository\Recipe\RecipeRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Recipe>
 */
final readonly class RecipePublicItemProvider implements ProviderInterface
{
    public function __construct(private RecipeRepository $recipeRepository)
    {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @throws NonUniqueResultException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Recipe
    {
        $id = $uriVariables['id'] ?? null;

        if (!is_string($id) && !is_int($id)) {
            throw new NotFoundHttpException('Recipe not found.');
        }

        $recipe = $this->recipeRepository->findOneOnlineForPublicApi((int) $id);

        if (null === $recipe) {
            throw new NotFoundHttpException('Recipe not found.');
        }

        return $recipe;
    }
}
