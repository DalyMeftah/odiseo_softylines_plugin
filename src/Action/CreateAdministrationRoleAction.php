<?php

declare(strict_types=1);

namespace Odiseo\SyliusRbacPlugin\Action;

use Odiseo\SyliusRbacPlugin\Message\CreateAdministrationRole;
use Odiseo\SyliusRbacPlugin\Normalizer\AdministrationRolePermissionNormalizerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CreateAdministrationRoleAction
{
    private const IMPORTABLE_RESOURCES = [
        'country',
        'customer_group',
        'payment_method',
        'tax_category',
        'customer',
        'product'
    ];

    private const EXPORTABLE_RESOURCES = [
        'country',
        'order',
        'customer',
        'product'
    ];

    public function __construct(
        private MessageBusInterface $bus,
        private AdministrationRolePermissionNormalizerInterface $administrationRolePermissionNormalizer,
        private UrlGeneratorInterface $router,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');

        try {
            /** @var array $administrationRolePermissions */
            $administrationRolePermissions = $request->request->all()['permissions'] ?? [];

            $normalizedPermissions = $this
                ->administrationRolePermissionNormalizer
                ->normalize($administrationRolePermissions)
            ;

            /** @var string $administrationRoleName */
            $administrationRoleName = $request->request->get('administration_role_name');

            $this->bus->dispatch(new CreateAdministrationRole(
                $administrationRoleName,
                $normalizedPermissions,
            ));

            $flashBag->add('success', 'odiseo_sylius_rbac_plugin.ui.administration_role_created');
        } catch (\Exception $exception) {
            $flashBag->add('error', $exception->getMessage());
        }

        return new RedirectResponse(
            $this->router->generate('odiseo_sylius_rbac_plugin_admin_administration_role_create_view'),
        );
    }
}
