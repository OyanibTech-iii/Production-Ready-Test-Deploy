<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ECertificateController extends AbstractController
{
    #[Route('/e-certificate', name: 'app_e-certificate')]
    public function index(): Response
    {
        return $this->render('e-certificate/index.html.twig', [
            'controller_name' => 'CertificateController',
        ]);
    }
}
