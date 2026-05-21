<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

final class MailerController extends AbstractController
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Route('/mailer', name: 'app_mailer')]
    public function index(): Response
    {
        return $this->render('mailer/index.html.twig', [
            'controller_name' => 'MailerController',
        ]);
    }

    public function sendLoginNotification(string $userEmail, string $userName): void
    {
        $email = (new Email())
            ->from('growficoofficial@gmail.com')
            ->to($userEmail)
            ->subject('Welcome back to Growfico!')
            ->html($this->renderView('emails/login_notification.html.twig', [
                'userName' => $userName,
            ]));

        $this->mailer->send($email);
    }
}
