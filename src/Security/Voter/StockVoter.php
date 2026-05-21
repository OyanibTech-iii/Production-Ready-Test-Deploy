<?php

namespace App\Security\Voter;

use App\Entity\Stock;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StockVoter extends Voter
{
    public const EDIT = 'STOCK_EDIT';
    public const DELETE = 'STOCK_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Stock;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);
        $isStaff = in_array('ROLE_STAFF', $roles, true);

        if ($isAdmin) {
            return true;
        }

        /** @var Stock $stock */
        $stock = $subject;

        // Staff may only manage their own stock; regular users are read-only
        if (!$isStaff) {
            return false;
        }

        return $stock->isOwnedBy($user);
    }
}

