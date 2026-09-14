<?php

namespace App\DataFixtures\Traits;

use App\Entity\Recipe\Category;
use App\Entity\Recipe\Recipe;
use App\Entity\Recipe\Tag;
use App\Entity\User\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $em = $container->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher');

        $admin = new Admin();
        $admin->setEmail('admin@test.com');
        $admin->setPassword($passwordHasher->hashPassword($admin, '@Password1986'));

        $em->persist($admin);
        $em->flush();

        $client->loginUser($admin);

        return $client;
    }
}
