<?php

namespace App\Tests;

use App\Entity\User\User;
use App\Message\User\UserResetPasswordMessage;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\TransportInterface;

class ResetPasswordControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);

        foreach ($userRepository->findAll() as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();
    }

    public function testResetPasswordController(): void
    {
        $user = (new User())
            ->setEmail('me@example.com')
            ->setPassword('a-test-password-that-will-be-changed-later')
        ;

        $this->em->persist($user);
        $this->em->flush();

        $userId = $user->getId();

        self::assertNotNull($userId);

        $this->client->request('GET', '/reset-password');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Mot de passe oublié');

        $this->client->submitForm('Envoyer le lien', [
            'reset_password_request_form[email]' => 'me@example.com',
        ]);

        /** @var TransportInterface $transport */
        $transport = static::getContainer()->get(
            'messenger.transport.async-user-reset-password'
        );

        $envelopes = iterator_to_array($transport->get());

        self::assertCount(1, $envelopes);

        $envelope = $envelopes[0];
        $message = $envelope->getMessage();

        self::assertInstanceOf(UserResetPasswordMessage::class, $message);
        self::assertSame($userId, $message->userId);

        self::assertResponseRedirects('/reset-password/check-email');
    }
}
