<?php

declare(strict_types=1);

namespace Odiseo\SyliusRbacPlugin\Entity;

use Odiseo\SyliusRbacPlugin\Model\Permission;
use Odiseo\SyliusRbacPlugin\Model\PermissionInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;

class AdministrationRole implements AdministrationRoleInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?string $name = null;

    protected array $permissions = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function addPermission(PermissionInterface $permission): void
    {
        $this->permissions[$permission->type()] = $permission->serialize();
    }

    public function removePermission(PermissionInterface $permission): void
    {
        unset($this->permissions[$permission->type()]);
    }

    public function clearPermissions(): void
    {
        $this->permissions = [];
    }

    public function hasPermission(PermissionInterface $permission): bool
    {
        return
            isset($this->permissions[$permission->type()]) &&
            $this->permissions[$permission->type()] === $permission->serialize()
        ;
    }

    public function getPermissions(): array
    {
        $permissions = [];
        /** @var string $permission */
        foreach ($this->permissions as $permission) {
            $permissions[] = Permission::unserialize($permission);
        }

        return $permissions;
    }
}
