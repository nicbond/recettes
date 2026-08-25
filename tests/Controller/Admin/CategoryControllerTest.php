<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategoryControllerTest extends WebTestCase
{
    private function createCategory(EntityManagerInterface $em, string $name = 'Apéritif'): Category
    {
        $category = new Category();
        $category->setName($name);
        $em->persist($category);
        $em->flush();

        return $category;
    }

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

    /**
     * @throws Exception
     */
    public function testDeleteCategory(): void
    {
        $client = CategoryControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $category = $this->createCategory($em, 'A supprimer '.uniqid());
        $categoryId = $category->getId();

        // Visit the page to initialize the session AND retrieve the token from the HTML
        $crawler = $client->request('GET', '/admin/categories/');

        // Retrieves the CSRF token directly from the deletion form on the page
        $form = $crawler->filter('form[action*="'.$categoryId.'"]')->first();
        $csrfToken = $form->filter('input[name="_token"]')->attr('value');

        $client->request('DELETE', '/admin/categories/'.$categoryId, [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/admin/categories/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne(
            'SELECT id FROM category WHERE id = ?',
            [$categoryId]
        );

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
}
