<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    public const EDIT = 'PRODUCT_EDIT';
    public const DELETE = 'PRODUCT_DELETE';
    public const VIEW = 'PRODUCT_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW], true) && $subject instanceof Product;
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

        /** @var Product $product */
        $product = $subject;

        // Admin can do anything
        if ($isAdmin) {
            return true;
        }

        // VIEW action: Staff can view any staff/admin product (read-only)
        if ($attribute === self::VIEW && $isStaff) {
            return true;
        }

        // EDIT and DELETE: Staff may only manage their own products
        if ($attribute === self::EDIT || $attribute === self::DELETE) {
            if (!$isStaff) {
                return false;
            }
            return $product->isOwnedBy($user);
        }

        return false;
    }
}

