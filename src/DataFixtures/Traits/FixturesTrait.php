<?php

namespace App\DataFixtures\Traits;

use App\Entity\Category;
use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;

trait FixturesTrait
{
    private function createCategory(EntityManagerInterface $em, string $name = 'Apéritif'): Category
    {
        $category = new Category();
        $category->setName($name.' '.uniqid());
        $em->persist($category);
        $em->flush();

        return $category;
    }

    private function createRecipe(EntityManagerInterface $em, string $title = 'Recette', ?Category $category = null): Recipe
    {
        if (null === $category) {
            $category = $this->createCategory($em);
        }

        $recipe = new Recipe();
        $recipe->setTitle($title.' '.uniqid());
        $recipe->setContent('Contenu de la recette de test');
        $recipe->setDuration(30);
        $recipe->setCategory($category);
        $recipe->setOnline(false);
        $em->persist($recipe);
        $em->flush();

        return $recipe;
    }
}
