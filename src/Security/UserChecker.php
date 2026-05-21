<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Global check: If inactive, stop authentication immediately
        if (method_exists($user, 'isActive') && !$user->isActive()) {
            throw new CustomUserMessageAuthenticationException(
                'Your account has been deactivated. Please contact support.'
            );
        }
        if (
            method_exists($user, 'isVerified')
            && !$user->isVerified()
            && method_exists($user, 'getProvider')
            && $user->getProvider() !== 'google'
        ) {
            throw new CustomUserMessageAuthenticationException(
                'Verified Account is Only Allowed to Login. Make sure to verify your email address before logging in.'
            );
        }

    }

    public function checkPostAuth(UserInterface $user): void
    {
        // This runs after the password has been checked. 
        // You can leave it empty or add additional checks here.
    }
}