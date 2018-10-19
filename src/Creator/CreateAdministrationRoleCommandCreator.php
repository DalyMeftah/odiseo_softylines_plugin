<?php

declare(strict_types=1);

namespace Sylius\RbacPlugin\Creator;

use Prooph\Common\Messaging\Command;
use Sylius\RbacPlugin\Command\CreateAdministrationRole;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

final class CreateAdministrationRoleCommandCreator implements CommandCreatorInterface
{
    public function fromRequest(Request $request): Command
    {
        /** @var ParameterBag $requestAttributes */
        $requestAttributes = $request->request;

        $command = new CreateAdministrationRole(
            $requestAttributes->get('administration_role_name'),
            $requestAttributes->get('permissions')
        );

        return $command;
    }
}
