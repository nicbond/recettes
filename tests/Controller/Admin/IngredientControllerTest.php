<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Ingredient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IngredientControllerTest extends WebTestCase
{
    private function createIngredient(EntityManagerInterface $em, string $name): Ingredient
    {
        $ingredient = new Ingredient();
        $ingredient->setName($name);
        $em->persist($ingredient);
        $em->flush();

        return $ingredient;
    }

    /**
     * @throws \JsonException
     */
    public function testCreateAjaxWithEmptyNameReturnsBadRequest(): void
    {
        $client = IngredientControllerTest::createClient();

        $client->request('POST', '/ingredient/create-ajax', [
            'name' => '',
        ]);

        self::assertResponseStatusCodeSame(400);

        $response = $this->decodeResponse($client);
        self::assertArrayHasKey('error', $response);
        self::assertSame('Le nom est obligatoire', $response['error']);
    }

    public function testCreateAjaxWithMissingNameReturnsBadRequest(): void
    {
        $client = static::createClient();

        $client->request('POST', '/ingredient/create-ajax', []);

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateAjaxCreatesNewIngredient(): void
    {
        $client = IngredientControllerTest::createClient();

        $client->request('POST', '/ingredient/create-ajax', [
            'name' => 'Frites '.uniqid(),
        ]);

        self::assertResponseStatusCodeSame(201);

        $response = $this->decodeResponse($client);
        self::assertArrayHasKey('value', $response);
        self::assertArrayHasKey('text', $response);
        self::assertNotNull($response['value']);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateAjaxWithExistingIngredientReturnsExisting(): void
    {
        $client = IngredientControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        $name = 'Tomate '.uniqid();
        $ingredient = $this->createIngredient($em, $name);

        $client->request('POST', '/ingredient/create-ajax', [
            'name' => $name,
        ]);

        self::assertResponseStatusCodeSame(200);

        $response = $this->decodeResponse($client);
        self::assertSame($ingredient->getId(), $response['value']);
        self::assertSame($name, $response['text']);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateAjaxIsCaseInsensitive(): void
    {
        $client = IngredientControllerTest::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);

        $name = 'Oignon '.uniqid();
        $ingredient = $this->createIngredient($em, $name);

        $client->request('POST', '/ingredient/create-ajax', [
            'name' => strtoupper($name),
        ]);

        self::assertResponseStatusCodeSame(200);

        $response = $this->decodeResponse($client);
        self::assertSame($ingredient->getId(), $response['value']);
    }

    public function testCreateAjaxOnlyAcceptsPostMethod(): void
    {
        $client = IngredientControllerTest::createClient();

        $client->request('GET', '/ingredient/create-ajax');

        self::assertResponseStatusCodeSame(405);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function decodeResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        assert(is_string($content));

        /** @var array<string, mixed> $response */
        $response = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return $response;
    }
}
