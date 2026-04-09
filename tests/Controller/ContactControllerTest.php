<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContactControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = ContactControllerTest::createClient();
        $client->request('GET', '/contact');

        self::assertResponseIsSuccessful();
    }
}
