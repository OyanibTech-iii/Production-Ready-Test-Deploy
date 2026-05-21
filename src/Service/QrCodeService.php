<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserQrCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrCodeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function generateForUser(User $user): UserQrCode
    {
        // Create unique identifier
        $identifier = bin2hex(random_bytes(16));
        
        $userQrCode = $user->getQrCode();
        if (!$userQrCode) {
            $userQrCode = new UserQrCode();
            $userQrCode->setUser($user);
        }

        $userQrCode->setIdentifier($identifier);
        $userQrCode->setCreatedAt(new \DateTimeImmutable());
        $userQrCode->setQrCodePath(null); // No longer needed as we use frontend generation

        $this->entityManager->persist($userQrCode);
        $this->entityManager->flush();

        return $userQrCode;
    }
}
