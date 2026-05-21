<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrivaypolicyController extends AbstractController
{
    #[Route('/privaypolicy', name: 'app_privaypolicy')]
    public function index(): Response
    {
        return $this->render('privaypolicy/index.html.twig', [
            'controller_name' => 'PrivaypolicyController',
        ]);
    }
}
