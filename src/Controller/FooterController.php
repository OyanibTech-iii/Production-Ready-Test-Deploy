<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FooterController extends AbstractController
{
    #[Route('/footer', name: 'app_footer')]
    public function index(): Response
    {
        return $this->render('footer/index.html.twig', [
            'controller_name' => 'FooterController',
        ]);
    }

    #[Route('/footer/fallback', name: 'app_footer_fallback')]
    public function fallback(): Response
    {
        return $this->render('footer/fallback.html.twig', [
            'controller_name' => 'FooterController',
        ]);
    }
}
