<?php

declare(strict_types=1);

namespace Odiseo\SyliusRbacPlugin\Cli\Granter;

use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusRbacPlugin\Access\Model\OperationType;
use Odiseo\SyliusRbacPlugin\Entity\AdministrationRoleAwareInterface;
use Odiseo\SyliusRbacPlugin\Entity\AdministrationRoleInterface;
use Odiseo\SyliusRbacPlugin\Factory\AdministrationRoleFactoryInterface;
use Odiseo\SyliusRbacPlugin\Model\Permission;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class AdministratorAccessGranter implements AdministratorAccessGranterInterface
{
    public function __construct(
        private RepositoryInterface $administratorRepository,
        private RepositoryInterface $administratorRoleRepository,
        private ObjectManager $objectManager,
        private AdministrationRoleFactoryInterface $administrationRoleFactory,
    ) {
    }

    public function __invoke(string $email, string $roleName, array $sections): void
    {
        /** @var AdministrationRoleAwareInterface|null $admin */
        $admin = $this->administratorRepository->findOneBy(['email' => $email]);

        if (null === $admin) {
            throw new \InvalidArgumentException(
                sprintf('Administrator with email address %s does not exist. Aborting.', $email),
            );
        }

        $administrationRole = $this->getOrCreateAdministrationRole($roleName);

        foreach ($sections as $section) {
            $administrationRole->addPermission(
                Permission::ofType(
                    $section,
                    [OperationType::read(), OperationType::create(), OperationType::update(), OperationType::delete()],
                ),
            );
        }

        $this->administratorRoleRepository->add($administrationRole);
        $admin->setAdministrationRole($administrationRole);

        $this->objectManager->flush();
    }

    private function getOrCreateAdministrationRole(string $roleName): AdministrationRoleInterface
    {
        // This behaviour is debatable - either just override the selected role or throw an exception.
        /** @var AdministrationRoleInterface|null $administrationRole */
        $administrationRole = $this->administratorRoleRepository->findOneBy(['name' => $roleName]);

        if (null === $administrationRole) {
            $administrationRole = $this->administrationRoleFactory->createWithName($roleName);
        }

        return $administrationRole;
    }
}
