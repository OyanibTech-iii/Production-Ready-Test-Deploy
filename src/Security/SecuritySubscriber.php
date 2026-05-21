<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Skip security checks for public routes
        $publicRoutes = ['/login', '/register', '/verify/email', '/terms', '/privaypolicy', '/footer', '/', '/deactivated', '/support-request'];
        foreach ($publicRoutes as $route) {
            if ($path === $route || str_starts_with($path, $route)) {
                return;
            }
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            return;
        }

        /** @var User $user */
        $user = $token->getUser();

        // Check if user account is verified for protected routes
        // Allow admin users to bypass email verification requirement
        if (!$user->isVerified() && !str_starts_with($path, '/verify/')) {
            $userRoles = array_map('strtoupper', $user->getRoles());
            
            // Allow admin users to access the application without email verification
            if (!in_array('ROLE_ADMIN', $userRoles, true)) {
                $event->setResponse(
                    new RedirectResponse($this->urlGenerator->generate('app_verify_email'))
                );
                return;
            }
        }

        // Additional security checks can be added here
        // For example: account lockout, IP restrictions, etc.
        // Prevent deactivated accounts from accessing the app
        if (!$user->isActive()) {
            // If already on deactivated page, allow rendering
            if ($path === '/deactivated' || str_starts_with($path, '/_profiler')) {
                return;
            }

            $event->setResponse(
                new RedirectResponse($this->urlGenerator->generate('app_deactivated'))
            );
            return;
        }
    }
}
