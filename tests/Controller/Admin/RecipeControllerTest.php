<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Recipe;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RecipeControllerTest extends WebTestCase
{
    private function createRecipe(EntityManagerInterface $em, string $title = 'Recette'): Recipe
    {
        $recipe = new Recipe();
        $recipe->setTitle($title.' '.uniqid());
        $recipe->setContent('Contenu de la recette de test');
        $recipe->setDuration(30); // inférieure à 60 → apparaît dans la liste
        $em->persist($recipe);
        $em->flush();

        return $recipe;
    }

    public function testIndex(): void
    {
        $client = RecipeControllerTest::createClient();
        $client->request('GET', '/admin/recettes/');

        self::assertResponseIsSuccessful();
    }

    public function testCreatePageIsSuccessful(): void
    {
        $client = RecipeControllerTest::createClient();
        $client->request('GET', '/admin/recettes/create');

        self::assertResponseIsSuccessful();
    }

    public function testCreateRecipe(): void
    {
        $client = RecipeControllerTest::createClient();
        $client->request('GET', '/admin/recettes/create');

        $client->submitForm('Créer', [
            'recipe[title]' => 'Burger '.uniqid(),
            'recipe[content]' => 'Contenu de la recette test',
            'recipe[duration]' => 30,
        ]);

        self::assertResponseRedirects('/admin/recettes/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-success', 'La recette a bien été créée');
    }

    public function testCreateRecipeWithInvalidData(): void
    {
        $client = RecipeControllerTest::createClient();
        $client->request('GET', '/admin/recettes/create');

        $client->submitForm('Créer', [
            'recipe[title]' => 'ab', // fewer than 5 characters → invalid
            'recipe[content]' => 'ab', // fewer than 5 characters → invalid
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
    }

    public function testEditPageIsSuccessful(): void
    {
        $client = RecipeControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $recipe = $this->createRecipe($em);

        $client->request('GET', '/admin/recettes/'.$recipe->getId());

        self::assertResponseIsSuccessful();
    }

    public function testEditRecipe(): void
    {
        $client = RecipeControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $recipe = $this->createRecipe($em);

        $client->request('GET', '/admin/recettes/'.$recipe->getId());

        $client->submitForm('Editer', [
            'recipe[title]' => 'Burger modifié '.uniqid(),
            'recipe[content]' => 'Contenu modifié de test',
            'recipe[duration]' => 45,
        ]);

        self::assertResponseRedirects('/admin/recettes/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-success', 'La recette a bien été modifiée');
    }

    public function testEditRecipeNotFound(): void
    {
        $client = RecipeControllerTest::createClient();
        $client->request('GET', '/admin/recettes/99999');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @throws Exception
     */
    public function testDeleteRecipe(): void
    {
        $client = RecipeControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $recipe = $this->createRecipe($em, 'A supprimer');
        $recipeId = $recipe->getId();

        // Search for the recipe on all pages
        $csrfToken = null;
        $page = 1;
        while (true) {
            $crawler = $client->request('GET', '/admin/recettes/?page='.$page);
            $form = $crawler->filter('form[action="/admin/recettes/'.$recipeId.'"]');
            if ($form->count() > 0) {
                $csrfToken = $form->filter('input[name="_token"]')->attr('value');
                break;
            }
            // Checks if there is a next page
            if (0 === $crawler->filter('a[rel="next"]')->count()) {
                break;
            }
            ++$page;
        }

        self::assertNotNull($csrfToken, 'CSRF token not found for recipe '.$recipeId);

        $client->request('POST', '/admin/recettes/'.$recipeId, [
            '_token' => $csrfToken,
            '_method' => 'DELETE',
        ]);

        self::assertResponseRedirects('/admin/recettes/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne(
            'SELECT id FROM recipe WHERE id = ?',
            [$recipeId]
        );

        self::assertFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testDeleteRecipeWithInvalidCsrfToken(): void
    {
        $client = RecipeControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $recipe = $this->createRecipe($em, 'Ne doit pas être supprimée');
        $recipeId = $recipe->getId();

        $client->request('DELETE', '/admin/recettes/'.$recipeId, [
            '_token' => 'invalid_token',
        ]);

        self::assertResponseRedirects('/admin/recettes/');

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne(
            'SELECT id FROM recipe WHERE id = ?',
            [$recipeId]
        );

        self::assertNotFalse($result);
    }
}
