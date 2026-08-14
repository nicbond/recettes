<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContactControllerTest extends WebTestCase
{
    public function testContactPageIsSuccessful(): void
    {
        $client = ContactControllerTest::createClient();
        $client->request('GET', '/contact');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testContactFormWithInvalidData(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $client->submitForm('Envoyer', [
            'contact[name]' => '',
            'contact[email]' => '',
            'contact[message]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
    }

    public function testContactFormWithValidData(): void
    {
        $client = ContactControllerTest::createClient();
        $client->request('GET', '/contact');

        $client->submitForm('Envoyer', [
            'contact[name]' => 'Nicolas Martins',
            'contact[email]' => 'nicolas@example.com',
            'contact[phone]' => '0660764689',
            'contact[service]' => 'service-technique@test.fr',
            'contact[message]' => 'Ceci est un message de test.',
        ]);

        self::assertResponseRedirects('/contact');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }
}
