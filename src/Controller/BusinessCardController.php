<?php

namespace App\Controller;

use App\Repository\UserQrCodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BusinessCardController extends AbstractController
{
    #[Route('/card/{identifier}', name: 'app_business_card', methods: ['GET'])]
    public function show(string $identifier, UserQrCodeRepository $qrCodeRepository): Response
    {
        $userQrCode = $qrCodeRepository->findOneBy(['identifier' => $identifier]);

        if (!$userQrCode) {
            throw $this->createNotFoundException('Business card not found.');
        }

        $user = $userQrCode->getUser();

        return $this->render('business_card/show.html.twig', [
            'user' => $user,
        ]);
    }
}
