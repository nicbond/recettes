<?php

namespace App\Tests\Controller;

use App\Entity\User\Admin;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TwoFactorControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private const EMAIL = 'email@example.com';
    private const PASSWORD = 'password';
    private const SECRET = 'JBSWY3DPEHPK3PXP';

    protected function setUp(): void
    {
        $this->client = TwoFactorControllerTest::createClient();

        $container = TwoFactorControllerTest::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $userRepository = $em->getRepository(Admin::class);

        foreach ($userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher');

        $user = (new Admin())
            ->setEmail(self::EMAIL)
            ->setIsVerified(true);

        $user->setPassword(
            $passwordHasher->hashPassword($user, self::PASSWORD)
        );

        $em->persist($user);
        $em->flush();
    }

    public function testLoginWithTwoFactorAuthentication(): void
    {
        $this->enableTwoFactorAuthentication();

        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Connexion', [
            '_username' => self::EMAIL,
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();

        self::assertResponseRedirects('/2fa');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            'h1',
            'Vérification en deux étapes'
        );
    }

    private function enableTwoFactorAuthentication(): void
    {
        $container = TwoFactorControllerTest::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $userRepository = $em->getRepository(Admin::class);

        /** @var Admin $user */
        $user = $userRepository->findOneBy([
            'email' => self::EMAIL,
        ]);

        self::assertNotNull($user);

        $user->setGoogleAuthenticatorSecret(self::SECRET);
        self::assertTrue($user->isGoogleAuthenticatorEnabled());

        $em->flush();
        $em->clear();

        /** @var Admin|null $reloadedUser */
        $reloadedUser = $em
            ->getRepository(Admin::class)
            ->findOneBy([
                'email' => self::EMAIL,
            ]);

        self::assertNotNull($reloadedUser);
        self::assertTrue(
            $reloadedUser->isGoogleAuthenticatorEnabled()
        );
    }

    public function testLoginWithInvalidTwoFactorCode(): void
    {
        $this->enableTwoFactorAuthentication();

        $this->client->request('GET', '/login');

        $this->client->submitForm('Connexion', [
            '_username' => self::EMAIL,
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();

        self::assertResponseRedirects('/2fa');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();

        $this->client->submitForm('Vérifier', [
            '_auth_code' => '000000',
        ]);

        self::assertResponseRedirects('/2fa');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    public function testLoginWithValidTwoFactorCode(): void
    {
        $this->enableTwoFactorAuthentication();

        $this->client->request('GET', '/login');

        $this->client->submitForm('Connexion', [
            '_username' => self::EMAIL,
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();

        self::assertResponseRedirects('/2fa');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();

        $totp = TOTP::createFromSecret(self::SECRET)
            ->withDigits(6)
            ->withPeriod(30)
            ->withDigest('sha1');

        $code = $totp->now();

        $this->client->submitForm('Vérifier', [
            '_auth_code' => $code,
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
    }
}
