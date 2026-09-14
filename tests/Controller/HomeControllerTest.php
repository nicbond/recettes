<?php

namespace App\Tests\Controller;

use App\DataFixtures\Traits\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    use FixturesTrait;

    public function testIndex(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
