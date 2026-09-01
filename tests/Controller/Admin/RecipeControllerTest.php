<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\Traits\FixturesTrait;
use App\Entity\Category;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class RecipeControllerTest extends WebTestCase
{
    use FixturesTrait;

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

        $doctrine = $client->getContainer()->get('doctrine');
        assert($doctrine instanceof ManagerRegistry);
        $em = $doctrine->getManager();
        assert($em instanceof EntityManagerInterface);

        $category = new Category();
        $category->setName('Plat principal');
        $em->persist($category);
        $em->flush();

        // We perform a GET request to open the session and retrieve the form.
        $crawler = $client->request('GET', '/admin/recettes/create');

        // The HTML form is extracted.
        $form = $crawler->selectButton('Créer')->form();

        // We are disabling strict validation of HTML options!
        // Cela empêche Symfony de râler si la liste déroulante lui semble vide dans le test
        /** @var ChoiceFormField $categoryField */
        $categoryField = $form['recipe[category]'];
        $categoryField->disableValidation();

        $form->setValues([
            'recipe[title]' => 'Burger '.uniqid(),
            'recipe[content]' => 'Contenu de la recette test',
            'recipe[duration]' => 30,
            'recipe[online]' => false,
            'recipe[category]' => $category->getId(),
        ]);

        $client->submit($form);

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

        $category = $recipe->getCategory();
        assert(null !== $category);

        $client->request('GET', '/admin/recettes/'.$recipe->getId());

        $client->submitForm('Éditer', [
            'recipe[title]' => 'Burger modifié '.uniqid(),
            'recipe[content]' => 'Contenu modifié de test',
            'recipe[duration]' => 45,
            'recipe[online]' => false,
            'recipe[category]' => $category->getId(),
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

    public function testEditThumbnailPageNotFound(): void
    {
        $client = RecipeControllerTest::createClient();
        $client->request('GET', '/admin/recettes/99999/edit-thumbnail');

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditThumbnailWithValidFile(): void
    {
        $client = RecipeControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $recipe = $this->createRecipe($em);

        $uploadedFile = new UploadedFile(
            __DIR__.'/../../fixtures/valid_image.png',
            'valid_image.png',
            'image/png',
            null,
            true
        );

        // Initialize the session with a GET request first
        $crawler = $client->request('GET', '/admin/recettes/'.$recipe->getId().'/edit-thumbnail');

        // Retrieves the CSRF token from the HTML form
        $csrfToken = $crawler->filter('input[name="recipe_thumbnail[_token]"]')->attr('value');

        // The solution using $client->request('POST', ...) is the most robust approach here.
        // It bypasses the Crawler's limitations regarding the asynchronous rendering of the Turbo modal,
        // while cleanly passing the file and CSRF token to Symfony.
        $client->request(
            'POST',
            '/admin/recettes/'.$recipe->getId().'/edit-thumbnail',
            [
                'recipe_thumbnail' => [
                    'thumbnailFile' => $uploadedFile,
                    '_token' => $csrfToken,
                ],
            ]
        );

        self::assertResponseRedirects('/admin/recettes/');
    }

    public function testEditThumbnailWithInvalidFile(): void
    {
        $client = RecipeControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $recipe = $this->createRecipe($em);

        $client->request('GET', '/admin/recettes/'.$recipe->getId().'/edit-thumbnail');

        $uploadedFile = new UploadedFile(
            __DIR__.'/../../fixtures/invalid_file.txt',
            'invalid_file.txt',
            'text/plain',
            null,
            true
        );

        // Why POST method et not PATCH, because it's not an API
        // Only the admin back office can create, modify, or delete: It is a business decision.
        $client->request('POST', '/admin/recettes/'.$recipe->getId().'/edit-thumbnail',
            [
                'recipe_thumbnail' => ['thumbnailFile' => $uploadedFile],
            ]
        );

        self::assertResponseStatusCodeSame(422);
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
