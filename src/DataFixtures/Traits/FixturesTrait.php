<?php

namespace App\DataFixtures\Traits;

use App\Entity\Recipe\Category;
use App\Entity\Recipe\Recipe;
use App\Entity\Recipe\Tag;
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

    private function createTag(EntityManagerInterface $em, string $name = 'facile'): Tag
    {
        $tag = new Tag();
        $tag->setName($name.' '.uniqid());
        $em->persist($tag);
        $em->flush();

        return $tag;
    }
}
