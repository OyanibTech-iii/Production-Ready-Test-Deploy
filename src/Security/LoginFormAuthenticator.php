<?php

namespace App\Security;

use App\Entity\User;
use App\Service\AuthEmailService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use App\Repository\UserRepository;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AuthEmailService $authEmailService,
        private UserRepository $userRepository
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Check if user exists and is active before attempting authentication
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user && method_exists($user, 'isActive') && !$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Your account has been deactivated. Please contact support for assistance.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Check if there's a target path (user was redirected to login from a protected page)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        /** @var User $user */
        $user = $token->getUser();

        error_log('Login successful for user: ' . $user->getEmail() . ' with roles: ' . implode(', ', $user->getRoles()));

        try {
            $this->authEmailService->sendLoginNotification($user->getEmail(), $user->getFirstName());
        } catch (\Exception $e) {
            error_log('Failed to send login notification: ' . $e->getMessage());
        }

        if ($this->hasRole($user, 'ROLE_ADMIN')) {
            $url = $this->urlGenerator->generate('app_admin_dashboard');
            error_log('Redirecting admin user to: ' . $url);
            return new RedirectResponse($url);
        }

        if ($this->hasRole($user, 'ROLE_STAFF')) {
            $url = $this->urlGenerator->generate('app_user_page');
            error_log('Redirecting moderator user to: ' . $url);
            return new RedirectResponse($url);
        }

        // Default redirect for regular users
        $url = $this->urlGenerator->generate('app_user_page');
        error_log('Redirecting regular user to: ' . $url);
        return new RedirectResponse($url);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Store the last username for the login form
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $request->request->get('email', ''));
        // If the failure was due to a deactivated account, redirect to deactivated page
        if ($exception->getMessageKey() === 'Your account has been deactivated. Please contact support.') {
             return new RedirectResponse($this->urlGenerator->generate('app_deactivated'));
        }
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            return new RedirectResponse($this->urlGenerator->generate('app_deactivated'));
        }

        // Let the parent handle other failures (redirect to login page)
        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * Check if user has a specific role (case-insensitive)
     */
    private function hasRole(User $user, string $role): bool
    {
        $userRoles = array_map('strtoupper', $user->getRoles());
        return in_array(strtoupper($role), $userRoles, true);
    }
}
