<?php

declare(strict_types=1);

namespace App\Tests\Manager;

use App\Entity\Recipe\Recipe;
use App\Manager\RecipeManager;
use App\Repository\Recipe\RecipeRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RecipeManagerTest extends TestCase
{
    private RecipeRepository&MockObject $recipeRepository;

    private RecipeManager $recipeManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->recipeRepository = $this->createMock(RecipeRepository::class);

        $this->recipeManager = new RecipeManager(
            $this->recipeRepository,
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdatePromotedRecipes(): void
    {
        $recipe1 = $this->createRecipe(1);
        $recipe2 = $this->createRecipe(2);
        $recipe3 = $this->createRecipe(3);

        $this->recipeRepository
            ->expects(self::once())
            ->method('findByIds')
            ->willReturn([$recipe1, $recipe2, $recipe3]);

        $this->recipeRepository
            ->expects(self::once())
            ->method('removePromotionFromOtherRecipes');

        $this->recipeRepository
            ->expects(self::once())
            ->method('flush');

        $this->recipeManager->updatePromotedRecipes([
            '1',
            '2',
            '3',
        ]);

        self::assertTrue($recipe1->isPromoted());
        self::assertSame(1, $recipe1->getPosition());

        self::assertTrue($recipe2->isPromoted());
        self::assertSame(2, $recipe2->getPosition());

        self::assertTrue($recipe3->isPromoted());
        self::assertSame(3, $recipe3->getPosition());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePromotedRecipesRespectsSubmittedOrder(): void
    {
        $recipe1 = $this->createRecipe(1);
        $recipe2 = $this->createRecipe(2);
        $recipe3 = $this->createRecipe(3);

        $this->recipeRepository
            ->expects(self::once())
            ->method('findByIds')
            ->willReturn([$recipe1, $recipe2, $recipe3]);

        $this->recipeRepository
            ->expects(self::once())
            ->method('removePromotionFromOtherRecipes');

        $this->recipeRepository
            ->expects(self::once())
            ->method('flush');

        $this->recipeManager->updatePromotedRecipes([
            '3',
            '1',
            '2',
        ]);

        self::assertTrue($recipe3->isPromoted());
        self::assertSame(1, $recipe3->getPosition());

        self::assertTrue($recipe1->isPromoted());
        self::assertSame(2, $recipe1->getPosition());

        self::assertTrue($recipe2->isPromoted());
        self::assertSame(3, $recipe2->getPosition());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePromotedRecipesWithEmptyListDisablesAllPromotedRecipes(): void
    {
        $this->recipeRepository
            ->expects(self::never())
            ->method('findByIds');

        $this->recipeRepository
            ->expects(self::once())
            ->method('removePromotionFromAll');

        $this->recipeRepository
            ->expects(self::once())
            ->method('flush');

        $this->recipeManager->updatePromotedRecipes([]);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePromotedRecipesRemovesDuplicateIds(): void
    {
        $recipe1 = $this->createRecipe(1);
        $recipe2 = $this->createRecipe(2);

        $this->recipeRepository
            ->expects(self::once())
            ->method('findByIds')
            ->willReturn([$recipe1, $recipe2]);

        $this->recipeRepository
            ->expects(self::once())
            ->method('removePromotionFromOtherRecipes');

        $this->recipeRepository
            ->expects(self::once())
            ->method('flush');

        $this->recipeManager->updatePromotedRecipes([
            '1',
            '2',
            '1',
            '2',
        ]);

        self::assertTrue($recipe1->isPromoted());
        self::assertSame(1, $recipe1->getPosition());

        self::assertTrue($recipe2->isPromoted());
        self::assertSame(2, $recipe2->getPosition());
    }

    public function testUpdatePromotedRecipesRejectsInvalidId(): void
    {
        $this->recipeRepository
            ->expects(self::never())
            ->method('findByIds');

        $this->recipeRepository
            ->expects(self::never())
            ->method('flush');

        $this->expectException(\DomainException::class);

        $this->recipeManager->updatePromotedRecipes([
            '1',
            'abc',
            '3',
        ]);
    }

    public function testUpdatePromotedRecipesRejectsZeroId(): void
    {
        $this->recipeRepository
            ->expects(self::once())
            ->method('findByIds')
            ->willReturn([]);

        $this->expectException(\DomainException::class);

        $this->recipeManager->updatePromotedRecipes(['0']);
    }

    public function testUpdatePromotedRecipesRejectsNegativeId(): void
    {
        $this->recipeRepository
            ->expects(self::never())
            ->method('findByIds');

        $this->expectException(\DomainException::class);

        $this->recipeManager->updatePromotedRecipes(['-1']);
    }

    public function testUpdatePromotedRecipesRejectsMoreThanTenRecipes(): void
    {
        $this->recipeRepository
            ->expects(self::never())
            ->method('findByIds');

        $this->expectException(\DomainException::class);

        $this->recipeManager->updatePromotedRecipes([
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11',
        ]);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePromotedRecipesRejectsUnknownRecipe(): void
    {
        $recipe1 = $this->createRecipe(1);

        $this->recipeRepository
            ->expects(self::once())
            ->method('findByIds')
            ->willReturn([$recipe1]);

        $this->expectException(\DomainException::class);

        $this->recipeManager->updatePromotedRecipes([
            '1',
            '999',
        ]);
    }

    /**
     * @throws Exception
     */
    private function createRecipe(int $id): Recipe
    {
        $promoted = false;
        $position = null;

        // Remplacement par createStub pour être en conformité avec PHPUnit 12
        $recipe = $this->createStub(Recipe::class);

        $recipe
            ->method('getId')
            ->willReturn($id);

        $recipe
            ->method('isPromoted')
            ->willReturnCallback(
                static function () use (&$promoted): bool {
                    return $promoted;
                },
            );

        $recipe
            ->method('setPromoted')
            ->willReturnCallback(
                static function (bool $value) use (&$promoted, $recipe): Recipe {
                    $promoted = $value;

                    return $recipe;
                },
            );

        $recipe
            ->method('getPosition')
            ->willReturnCallback(
                static function () use (&$position): ?int {
                    return $position;
                },
            );

        $recipe
            ->method('setPosition')
            ->willReturnCallback(
                static function (?int $value) use (&$position, $recipe): Recipe {
                    $position = $value;

                    return $recipe;
                },
            );

        return $recipe;
    }
}
