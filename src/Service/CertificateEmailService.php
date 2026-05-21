<?php

namespace App\Service;

use App\Entity\Certificate;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class CertificateEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    public function sendCertificatePdfToUser(Certificate $certificate, string $pdfBinary, string $pdfFilename): void
    {
        $user = $certificate->getUser();
        if ($user === null) {
            throw new \InvalidArgumentException('Certificate user is missing.');
        }

        $recipientEmail = trim((string) $user->getEmail());
        if ($recipientEmail === '' || filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Recipient email is invalid.');
        }

        $certificateCode = $certificate->getCertificateCode() ?? '';
        $recipientName = trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
        if ($recipientName === '') {
            $recipientName = $recipientEmail;
        }

        $email = (new Email())
            ->from(new Address('growficoofficial@gmail.com', 'Growfico'))
            ->sender(new Address('growficoofficial@gmail.com', 'Growfico'))
            ->to($recipientEmail)
            ->subject(sprintf('Your Growfico Certificate (%s)', $certificateCode !== '' ? $certificateCode : ''))
            ->html($this->getEmailHtml($recipientName, $certificateCode))
            ->attach($pdfBinary, $pdfFilename, 'application/pdf');

        $this->mailer->send($email);
    }

    private function getEmailHtml(string $recipientName, string $certificateCode): string
    {
        $certificateCodeHtml = htmlspecialchars($certificateCode, ENT_QUOTES, 'UTF-8');
        $recipientNameHtml = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<p>Hi {$recipientNameHtml},</p>
<p>Congratulations! Your Growfico certificate is attached as a PDF.</p>
<p><strong>Certificate Code:</strong> {$certificateCodeHtml}</p>
<p>Best regards,<br/>Growfico Team</p>
HTML;
    }
}
