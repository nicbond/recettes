<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\Traits\FixturesTrait;
use App\Entity\Recipe;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TagControllerTest extends WebTestCase
{
    use FixturesTrait;

    public function testIndex(): void
    {
        $client = TagControllerTest::createClient();
        $client->request('GET', '/admin/tags/');

        self::assertResponseIsSuccessful();
    }

    public function testCreatePageIsSuccessful(): void
    {
        $client = TagControllerTest::createClient();
        $client->request('GET', '/admin/tags/create');

        self::assertResponseIsSuccessful();
    }

    public function testCreateTag(): void
    {
        $client = TagControllerTest::createClient();
        $client->request('GET', '/admin/tags/create');

        $client->submitForm('Créer', [
            'tag[name]' => 'facile-'.uniqid(),
        ]);

        self::assertResponseRedirects('/admin/tags/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-success', 'Le tag a bien été créé');
    }

    public function testCreateTagWithInvalidData(): void
    {
        $client = TagControllerTest::createClient();
        $client->request('GET', '/admin/tags/create');

        $client->submitForm('Créer', [
            'tag[name]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testEditPageIsSuccessful(): void
    {
        $client = TagControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $tag = $this->createTag($em);

        $client->request('GET', '/admin/tags/'.$tag->getId());

        self::assertResponseIsSuccessful();
    }

    public function testEditTag(): void
    {
        $client = TagControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $tag = $this->createTag($em);

        $client->request('GET', '/admin/tags/'.$tag->getId());

        $client->submitForm('Éditer', [
            'tag[name]' => 'modifie-'.uniqid(),
        ]);

        self::assertResponseRedirects('/admin/tags/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-success', 'Le tag a bien été modifié');
    }

    public function testEditTagNotFound(): void
    {
        $client = TagControllerTest::createClient();
        $client->request('GET', '/admin/tags/99999');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @throws Exception
     */
    public function testDeleteTag(): void
    {
        $client = TagControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        $tagName = 'ZZZ_A_supprimer_'.uniqid();
        $tag = $this->createTag($em, $tagName);
        $tagId = $tag->getId();

        $crawler = $client->request('GET', '/admin/tags/?sort=tag.name&direction=desc');
        self::assertResponseIsSuccessful();

        $formNode = $crawler->filter('form[action*="/admin/tags/'.$tagId.'"] input[name="_token"]')->first();
        $csrfToken = $formNode->attr('value');

        $client->request('DELETE', '/admin/tags/'.$tagId, [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/admin/tags/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne('SELECT id FROM category WHERE id = ?', [$tagId]);
        self::assertFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testDeleteTagWithInvalidCsrfToken(): void
    {
        $client = TagControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $tag = $this->createTag($em, 'Ne doit pas être supprimé');
        $tagId = $tag->getId();

        $client->request('DELETE', '/admin/tags/'.$tagId, [
            '_token' => 'invalid_token',
        ]);

        self::assertResponseRedirects('/admin/tags/');

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne(
            'SELECT id FROM tag WHERE id = ?',
            [$tagId]
        );

        self::assertNotFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testDeleteTagLinkedToRecipeIsNotDeleted(): void
    {
        $client = TagControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        $tag = $this->createTag($em, 'ZZZ_Tag_lié');
        $tagId = $tag->getId();

        $category = $this->createCategory($em, 'Catégorie lié');

        $recipe = new Recipe();
        $recipe
            ->setTitle('Recette liée '.uniqid())
            ->setContent('Contenu de test')
            ->setDuration(30)
            ->addTag($tag)
            ->setCategory($category);

        $em->persist($recipe);
        $em->flush();

        $crawler = $client->request('GET', '/admin/tags/?sort=tag.id&direction=desc');
        self::assertResponseIsSuccessful();

        $formNode = $crawler->filter('form[action*="/admin/tags/'.$tagId.'"] input[name="_token"]')->first();
        $csrfToken = $formNode->attr('value');

        $client->request('DELETE', '/admin/tags/'.$tagId, [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/admin/tags/');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Impossible de supprimer ce tag car il est lié à des recettes.');

        $connection = $client->getContainer()->get('doctrine.dbal.default_connection');
        assert($connection instanceof Connection);
        $result = $connection->fetchOne(
            'SELECT id FROM tag WHERE id = ?',
            [$tagId]
        );

        self::assertNotFalse($result);
    }
}
