<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class AuthEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    public function sendLoginNotification(string $recipientEmail, ?string $firstName = null): void
    {
        $normalizedEmail = trim($recipientEmail);
        if ($normalizedEmail === '' || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Recipient email is invalid.');
        }

        $safeFirstName = trim((string) $firstName);
        $displayName = $safeFirstName !== '' ? $safeFirstName : 'there';
        $displayNameHtml = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');

        $email = (new Email())
            ->from(new Address('growficoofficial@gmail.com', 'Growfico'))
            ->sender(new Address('growficoofficial@gmail.com', 'Growfico'))
            ->to($normalizedEmail)
            ->subject('Welcome back to Growfico!')
            ->html(<<<HTML
<h1>Welcome back, {$displayNameHtml}!</h1>
<p>Thank you for logging into Growfico. We're excited to have you back!</p>
<p>If you didn't log in to your account, please secure your account immediately.</p>
<p>Best regards,<br>The Growfico Team</p>
HTML);

        $this->mailer->send($email);
    }
}
