<?php

namespace App\Entity\User;

use App\Enum\Permission;
use App\Repository\User\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin extends User
{
    /**
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $permissions = [];

    public function getRoles(): array
    {
        return array_unique(array_merge(parent::getRoles(), ['ROLE_ADMIN']));
    }

    /**
     * @return list<string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param list<string> $permissions
     */
    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission->value, $this->permissions, true);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->getRoles(), true);
    }
}
