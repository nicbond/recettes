<?php

namespace App\Manager;

use App\Entity\User\Admin;
use App\Message\User\UserVerifyAccountMessage;
use App\Repository\User\AdminRepository;
use Random\RandomException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class AdminManager
{
    public function __construct(
        private AdminRepository $adminRepository,
        private MessageBusInterface $bus,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    public function createNewAdmin(Admin $admin, FormInterface $form): void
    {
        $plainPassword = $form->get('plainPassword')->getData();
        assert(is_string($plainPassword));

        $admin
            ->setConfirmationToken($this->generateSecureToken())
            ->setIsVerified(false)
            ->setPassword($this->userPasswordHasher->hashPassword($admin, $plainPassword));

        if ($form->has('roles') && null !== $form->get('roles')->getData()) {
            /** @var string[] $roles */
            $roles = $form->get('roles')->getData();
            $admin->setRoles(array_values($roles));
        }

        $this->saveAndDispatchVerification($admin);
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    public function resendVerifiedAccountEmail(Admin $admin): void
    {
        $admin->setConfirmationToken($this->generateSecureToken());
        $this->saveAndDispatchVerification($admin);
    }

    /**
     * The administrator persists and distributes the asynchronous verification message.
     *
     * @throws ExceptionInterface
     */
    private function saveAndDispatchVerification(Admin $admin): void
    {
        $email = $admin->getEmail();
        $token = $admin->getConfirmationToken();

        if (null === $email || null === $token) {
            throw new \LogicException('User email and confirmation token must not be null.');
        }

        $this->adminRepository->save($admin, true);
        $this->bus->dispatch(new UserVerifyAccountMessage($email, $token));
    }

    /**
     * Generates a secure cryptographic token.
     *
     * @throws RandomException
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
