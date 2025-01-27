<?php

declare(strict_types=1);

namespace Odiseo\SyliusRbacPlugin\Access\Listener;

use Odiseo\SyliusRbacPlugin\Access\Checker\AdministratorAccessCheckerInterface;
use Odiseo\SyliusRbacPlugin\Access\Checker\RouteNameCheckerInterface;
use Odiseo\SyliusRbacPlugin\Access\Creator\AccessRequestCreatorInterface;
use Odiseo\SyliusRbacPlugin\Access\Exception\InsecureRequestException;
use Odiseo\SyliusRbacPlugin\Access\Exception\UnresolvedRouteNameException;
use Odiseo\SyliusRbacPlugin\Access\Model\AccessRequest;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Webmozart\Assert\Assert;

final class AccessCheckListener
{
    public function __construct(
        private AccessRequestCreatorInterface $accessRequestCreator,
        private AdministratorAccessCheckerInterface $administratorAccessChecker,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $router,
        private RouteNameCheckerInterface $adminRouteChecker,
        private \Twig\Environment $twig,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        try {
            $accessRequest = $this->createAccessRequestFromEvent($event);
        } catch (InsecureRequestException $exception) {
            return;
        }

        if ($this->administratorAccessChecker->canAccessSection($this->getCurrentAdmin(), $accessRequest)) {
            return;
        }

        $response = new Response(
            $this->twig->render('@OdiseoSyliusRbacPlugin/Admin/Error/403.html.twig', [
                'message' => 'odiseo_sylius_rbac_plugin.you_have_no_access_to_this_section'
            ]),
            Response::HTTP_FORBIDDEN
        );
        
        $event->setResponse($response);
    }

    /** @throws InsecureRequestException */
    private function createAccessRequestFromEvent(RequestEvent $event): AccessRequest
    {
        if (!$event->isMainRequest()) {
            throw new InsecureRequestException();
        }

        /** @var string|null $routeName */
        $routeName = $event->getRequest()->attributes->get('_route');
        $requestMethod = $event->getRequest()->getMethod();

        if ($routeName === null) {
            throw new InsecureRequestException();
        }

        if (!$this->adminRouteChecker->isAdminRoute($routeName)) {
            throw new InsecureRequestException();
        }

        try {
            $accessRequest = $this->accessRequestCreator->createFromRouteName($routeName, $requestMethod);
        } catch (UnresolvedRouteNameException $exception) {
            throw new InsecureRequestException();
        }

        return $accessRequest;
    }

    private function getCurrentAdmin(): AdminUserInterface
    {
        $token = $this->tokenStorage->getToken();
        Assert::notNull($token);

        /** @var AdminUserInterface|null $currentAdmin */
        $currentAdmin = $token->getUser();
        Assert::isInstanceOf($currentAdmin, UserInterface::class);

        return $currentAdmin;
    }

    private function addAccessErrorFlash(string $requestMethod, SessionInterface $session): void
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $session->getBag('flashes');

        if ('GET' === $requestMethod || 'HEAD' === $requestMethod) {
            $flashBag->add('error', 'odiseo_sylius_rbac_plugin.you_have_no_access_to_this_section');

            return;
        }

        $flashBag->add('error', 'odiseo_sylius_rbac_plugin.you_are_not_allowed_to_do_that');
    }

    private function getRedirectResponse(?string $referer): RedirectResponse
    {
        if (null !== $referer) {
            return new RedirectResponse($referer);
        }

        return new RedirectResponse($this->router->generate('sylius_admin_dashboard'));
    }
}
