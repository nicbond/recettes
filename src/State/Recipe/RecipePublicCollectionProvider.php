<?php

declare(strict_types=1);

namespace App\State\Recipe;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Recipe\Recipe;
use App\Repository\Recipe\RecipeRepository;

/**
 * @implements ProviderInterface<Recipe>
 */
final readonly class RecipePublicCollectionProvider implements ProviderInterface
{
    public function __construct(private RecipeRepository $recipeRepository)
    {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return list<Recipe>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->recipeRepository->findOnlineForPublicApi();
    }
}
