<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function log(?User $user, string $action, string $target, ?array $changes = null): void
    {
        $log = new ActivityLog();

        if ($user) {
            $log->setUserId($user->getId());
            $log->setUsername($user->getUserIdentifier());
            $log->setRole($this->getPrimaryRole($user));
        }

        $log->setAction($action);
        $log->setTarget($target);
        if ($changes) {
            $log->setChanges($changes);
        }
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function getPrimaryRole(User $user): ?string
    {
        $roles = $user->getRoles();

        foreach (['ROLE_ADMIN', 'ROLE_STAFF', 'ROLE_USER'] as $candidate) {
            if (in_array($candidate, $roles, true)) {
                return $candidate;
            }
        }

        return $roles[0] ?? null;
    }
}

