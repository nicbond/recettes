<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\Traits\FixturesTrait;
use App\Entity\Recipe;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategoryControllerTest extends WebTestCase
{
    use FixturesTrait;

    public function testIndex(): void
    {
        $client = CategoryControllerTest::createClient();
        $client->request('GET', '/admin/categories/');

        self::assertResponseIsSuccessful();
    }

    public function testCreatePageIsSuccessful(): void
    {
        $client = CategoryControllerTest::createClient();
        $client->request('GET', '/admin/categories/create');

        self::assertResponseIsSuccessful();
    }

    public function testCreateCategory(): void
    {
        $client = CategoryControllerTest::createClient();
        $client->request('GET', '/admin/categories/create');

        $client->submitForm('Créer', [
            'category[name]' => 'Apéritif '.uniqid(),
            'category[slug]' => '',
        ]);

        self::assertResponseRedirects('/admin/categories/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-success', 'La catégorie a bien été créée');
    }

    public function testCreateCategoryWithInvalidData(): void
    {
        $client = CategoryControllerTest::createClient();
        $client->request('GET', '/admin/categories/create');

        $client->submitForm('Créer', [
            'category[name]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
    }

    public function testEditPageIsSuccessful(): void
    {
        $client = CategoryControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $category = $this->createCategory($em);

        $client->request('GET', '/admin/categories/'.$category->getId());

        self::assertResponseIsSuccessful();
    }

    public function testEditCategory(): void
    {
        $client = CategoryControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $category = $this->createCategory($em, 'Dessert '.uniqid());

        $client->request('GET', '/admin/categories/'.$category->getId());

        $client->submitForm('Editer', [
            'category[name]' => 'Dessert modifié '.uniqid(),
        ]);

        self::assertResponseRedirects('/admin/categories/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-success', 'La catégorie a bien été modifiée');
    }

    public function testEditCategoryNotFound(): void
    {
        $client = CategoryControllerTest::createClient();
        $client->request('GET', '/admin/categories/99999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteCategory(): void
    {
        $client = CategoryControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        // 1. Assign a strategic name for sorting
        $categoryName = 'ZZZ_A_supprimer_'.uniqid();
        $category = $this->createCategory($em, $categoryName);
        $categoryId = $category->getId();

        // 2. FIX: Force descending sort by name in the URL.
        // Our "ZZZ_" category will thus appear AT THE VERY TOP of page 1.
        $crawler = $client->request('GET', '/admin/categories/?sort=category.name&direction=desc');
        self::assertResponseIsSuccessful();

        // 3. Extracting the token from the actual HTML
        $formNode = $crawler->filter('form[action*="/admin/categories/'.$categoryId.'"] input[name="_token"]')->first();
        $csrfToken = $formNode->attr('value');

        // 4. Submitting the deletion using the expected DELETE method
        $client->request('DELETE', '/admin/categories/'.$categoryId, [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/admin/categories/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne('SELECT id FROM category WHERE id = ?', [$categoryId]);
        self::assertFalse($result);
    }

    public function testDeleteCategoryWithInvalidCsrfToken(): void
    {
        $client = CategoryControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $category = $this->createCategory($em, 'Ne doit pas être supprimée');

        $client->request('DELETE', '/admin/categories/'.$category->getId(), [
            '_token' => 'invalid_token',
        ]);

        self::assertResponseRedirects('/admin/categories/');

        $repository = $client->getContainer()->get(CategoryRepository::class);
        assert($repository instanceof CategoryRepository);
        $existingCategory = $repository->find($category->getId());

        self::assertNotNull($existingCategory);
    }

    /**
     * @throws Exception
     */
    public function testDeleteCategoryLinkedToRecipeIsNotDeleted(): void
    {
        $client = CategoryControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        $category = $this->createCategory($em, 'Catégorie liée '.uniqid());

        $recipe = new Recipe();
        $recipe->setTitle('Recette liée '.uniqid());
        $recipe->setContent('Contenu de test');
        $recipe->setDuration(30);
        $recipe->setCategory($category);
        $em->persist($recipe);
        $em->flush();

        $categoryId = $category->getId();

        // Using the same secure sort by category.id
        $crawler = $client->request('GET', '/admin/categories/?sort=category.id&direction=desc');
        self::assertResponseIsSuccessful();

        $formNode = $crawler->filter('form[action*="/admin/categories/'.$categoryId.'"] input[name="_token"]')->first();
        $csrfToken = $formNode->attr('value');

        $client->request('DELETE', '/admin/categories/'.$categoryId, [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/admin/categories/');
        $client->followRedirect();
        self::assertSelectorExists('.alert-danger');

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne('SELECT id FROM category WHERE id = ?', [$categoryId]);
        self::assertNotFalse($result);
    }
}
