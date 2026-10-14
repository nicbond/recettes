<?php

namespace App\DataFixtures;

use App\Entity\User\Admin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function getGroups(): array
    {
        return ['admin'];
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Admin();
        $admin
            ->setEmail('admin@admin.com')
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, '@Password1986!'))
            ->setIsVerified(true)
            ->setIsActive(true);

        $manager->persist($admin);
        $manager->flush();
    }
}
