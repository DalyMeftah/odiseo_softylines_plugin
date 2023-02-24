<?php

declare(strict_types=1);

namespace Odiseo\SyliusRbacPlugin\Action;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Odiseo\SyliusRbacPlugin\Entity\AdministrationRoleInterface;
use Odiseo\SyliusRbacPlugin\Extractor\PermissionDataExtractorInterface;
use Odiseo\SyliusRbacPlugin\Provider\AdminPermissionsProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class UpdateAdministrationRoleViewAction
{
    /** @var AdminPermissionsProviderInterface */
    private $adminPermissionsProvider;

    /** @var RepositoryInterface */
    private $administrationRoleRepository;

    /** @var PermissionDataExtractorInterface */
    private $permissionDataExtractor;

    /** @var Environment */
    private $twig;

    /** @var UrlGeneratorInterface */
    private $router;

    public function __construct(
        AdminPermissionsProviderInterface $adminPermissionsProvider,
        RepositoryInterface $administrationRoleRepository,
        PermissionDataExtractorInterface $permissionDataExtractor,
        Environment $twig,
        UrlGeneratorInterface $router
    ) {
        $this->adminPermissionsProvider = $adminPermissionsProvider;
        $this->administrationRoleRepository = $administrationRoleRepository;
        $this->permissionDataExtractor = $permissionDataExtractor;
        $this->twig = $twig;
        $this->router = $router;
    }

    public function __invoke(Request $request): Response
    {
        /** @var AdministrationRoleInterface|null $administrationRole */
        $administrationRole = $this->administrationRoleRepository->find($request->attributes->get('id'));

        if (null === $administrationRole) {
            $request->getSession()->getFlashBag()->add('error', [
                'message' => 'sylius_rbac.administration_role_not_found',
                'parameters' => ['%administration_role_id%' => $request->attributes->get('id')],
            ]);

            return new RedirectResponse($this->router->generate('sylius_rbac_admin_administration_role_index'));
        }

        return new Response(
            $this->twig->render('@OdiseoSyliusRbacPlugin/AdministrationRole/update.html.twig', [
                'administration_role' => $administrationRole,
                'permissions' => $this->adminPermissionsProvider->getPossiblePermissions(),
                'rolePermissions' => $administrationRole->getPermissions(),
            ])
        );
    }
}
