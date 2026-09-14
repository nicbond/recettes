<?php

namespace App\Entity\User;

use App\Repository\User\ConsumerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsumerRepository::class)]
class Consumer extends User
{
    public function getRoles(): array
    {
        return array_unique(parent::getRoles());
    }
}
