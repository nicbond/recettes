<?php

declare(strict_types=1);

namespace App\Manager;

use App\Repository\Recipe\RecipeRepository;

final readonly class RecipeManager
{
    private const int MAX_PROMOTED_RECIPES = 10;

    public function __construct(private RecipeRepository $recipeRepository)
    {
    }

    /**
     * @param list<string> $recipeIds
     */
    public function updatePromotedRecipes(array $recipeIds): void
    {
        $recipeIds = array_values(array_unique($recipeIds));

        if (count($recipeIds) > self::MAX_PROMOTED_RECIPES) {
            throw new \DomainException(sprintf('Impossible de mettre plus de %d recettes en avant.', self::MAX_PROMOTED_RECIPES));
        }

        foreach ($recipeIds as $recipeId) {
            if (!ctype_digit($recipeId) || (int) $recipeId <= 0) {
                throw new \DomainException('Un identifiant de recette est invalide.');
            }
        }

        $ids = array_map(
            static fn (string $id): int => (int) $id,
            $recipeIds
        );

        if ([] === $ids) {
            $this->recipeRepository->removePromotionFromAll();
            $this->recipeRepository->flush();

            return;
        }

        $recipes = $this->recipeRepository->findByIds($ids);

        if (count($recipes) !== count($ids)) {
            throw new \DomainException('Une ou plusieurs recettes sélectionnées sont introuvables.');
        }

        $recipesById = [];

        foreach ($recipes as $recipe) {
            $recipesById[$recipe->getId()] = $recipe;
        }

        /*
         * All selected recipes are first reset.
         */
        foreach ($recipesById as $recipe) {
            $recipe
                ->setPromoted(false)
                ->setPosition(null)
                ->setOnline(false);
        }

        /*
         * The order sent from the front end becomes the business position.
         */
        foreach ($ids as $position => $id) {
            $recipe = $recipesById[$id];

            $recipe
                ->setPromoted(true)
                ->setOnline(true)
                ->setPosition($position + 1);
        }

        /*
         * All other recipes must lose their "featured" status.
         */
        $this->recipeRepository->removePromotionFromOtherRecipes($ids);

        $this->recipeRepository->flush();
    }
}
