<?php

namespace App\Security;

use App\Entity\User;
use App\Service\AuthEmailService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private AuthEmailService $authEmailService,
        private UserPasswordHasherInterface $passwordHasher,
        private \App\Service\QrCodeService $qrCodeService
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Matches the route defined in your routing configuration
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // 1) Find user by Google ID or Email
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);

                if (!$user) {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                    if (!$user) {
                        // 2) Register a new user if they don't exist
                        $user = new User();
                        $user->setEmail($email);
                        $user->setFirstName($googleUser->getFirstName() ?? '');
                        $user->setLastName($googleUser->getLastName() ?? '');
                        $randomPassword = bin2hex(random_bytes(16));
                        $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));
                        $user->setRoles(['ROLE_STAFF']);
                    }
                    $user->setProvider('google');
                    $user->setIsVerified(true);
                    $user->setGoogleId($googleUser->getId());
                    // Optional: If you want to force existing users to STAFF when they link Google:
                    // $user->setRoles(['ROLE_STAFF']);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    // 2.1) Ensure QR Code exists for the user
                    if (!$user->getQrCode()) {
                        $this->qrCodeService->generateForUser($user);
                    }
                }

                // 3) Security Check: Prevent deactivated users from logging in
                if (method_exists($user, 'isActive') && !$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Your account has been deactivated. Please contact support.');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        error_log('Google login successful for user: ' . $user->getEmail());

        try {
            $this->authEmailService->sendLoginNotification($user->getEmail(), $user->getFirstName());
        } catch (\Exception $e) {
            error_log('Failed to send OAuth login notification: ' . $e->getMessage());
        }

        // 4) Role-based redirect logic (Consistent with LoginFormAuthenticator)
        if ($this->hasRole($user, 'ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        if ($this->hasRole($user, 'ROLE_MODERATOR')) {
            return new RedirectResponse($this->router->generate('app_user_page'));
        }

        return new RedirectResponse($this->router->generate('app_user_page'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Redirect deactivated users to the specific info page
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            return new RedirectResponse($this->router->generate('app_deactivated'));
        }

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    private function hasRole(User $user, string $role): bool
    {
        $userRoles = array_map('strtoupper', $user->getRoles());
        return in_array(strtoupper($role), $userRoles, true);
    }
}