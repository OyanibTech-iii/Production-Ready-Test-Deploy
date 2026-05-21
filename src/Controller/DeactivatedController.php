<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeactivatedController extends AbstractController
{
    #[Route('/deactivated', name: 'app_deactivated')]
    public function index(): Response
    {
        return $this->render('deactivated.html.twig', [
            'isDeactivated' => true,
        ]);
    }
}
