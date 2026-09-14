<?php

namespace App\Entity\User;

use App\Repository\User\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin extends User
{
    public function getRoles(): array
    {
        return array_unique(parent::getRoles());
    }
}
