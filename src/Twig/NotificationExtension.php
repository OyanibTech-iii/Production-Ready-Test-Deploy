<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security,
    ) {
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        // Only provide notifications if user is authenticated and is an admin
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            return [
                'notifications' => $this->notificationRepository->findBy(['user' => $user], ['createAt' => 'DESC'], 10),
            ];
        }

        return [
            'notifications' => [],
        ];
    }
}
