<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

final class ForgotPasswordEmailService
{
    private const TOKEN_EXPIRY_HOURS = 1;
    private const SENDER_EMAIL = 'growficoofficial@gmail.com';
    private const SENDER_NAME = 'Growfico';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a cryptographically secure password reset token.
     * 
     * @return string A 64-character hexadecimal token
     */
    public function generatePasswordResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Send password reset email to user.
     * 
     * @param User $user The user requesting password reset
     * @param string $resetUrl The full URL for password reset (including token)
     * @throws \InvalidArgumentException If email is invalid
     */
    public function sendPasswordResetEmail(User $user, string $resetUrl): void
    {
        $normalizedEmail = trim($user->getEmail() ?? '');
        if ($normalizedEmail === '' || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('User email is invalid.');
        }

        if (filter_var($resetUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Reset URL is invalid.');
        }

        $this->logger->info('Sending password reset email', [
            'user_id' => $user->getId(),
            'email' => $normalizedEmail,
            'from' => self::SENDER_EMAIL,
        ]);

        $email = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($user->getEmail(), $user->getFirstName() ?? 'User'))
            ->subject('Reset Your Growfico Password')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'reset_url' => $resetUrl,
                'expiry_hours' => self::TOKEN_EXPIRY_HOURS,
            ]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Password reset email sent successfully', [
                'user_id' => $user->getId(),
                'email' => $normalizedEmail,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'user_id' => $user->getId(),
                'email' => $normalizedEmail,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Create and store a password reset token for the user.
     * Generates a token and sets expiration time.
     * 
     * @param User $user The user requesting password reset
     * @return string The generated reset token
     */
    public function createPasswordResetToken(User $user): string
    {
        $token = $this->generatePasswordResetToken();
        $expiresAt = new \DateTimeImmutable(sprintf('+%d hours', self::TOKEN_EXPIRY_HOURS));

        $user->setPasswordResetToken($token);
        $user->setPasswordResetTokenExpiresAt($expiresAt);

        $this->entityManager->flush();

        return $token;
    }

    /**
     * Verify a password reset token.
     * 
     * @param string $token The token to verify
     * @return User|null The user associated with the token, or null if invalid/expired
     */
    public function verifyPasswordResetToken(string $token): ?User
    {
        if (empty($token) || strlen($token) !== 64) {
            return null;
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['passwordResetToken' => $token]);

        if (!$user) {
            return null;
        }

        // Check if token has expired
        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (!$expiresAt || $expiresAt < new \DateTimeImmutable('now')) {
            $this->clearPasswordResetToken($user);
            return null;
        }

        return $user;
    }

    /**
     * Clear the password reset token after successful reset or expiration.
     * 
     * @param User $user The user to clear the token for
     */
    public function clearPasswordResetToken(User $user): void
    {
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);
        $this->entityManager->flush();
    }

    /**
     * Check if user has an active password reset token.
     * 
     * @param User $user The user to check
     * @return bool True if user has a valid, non-expired token
     */
    public function hasActivePasswordResetToken(User $user): bool
    {
        $token = $user->getPasswordResetToken();
        $expiresAt = $user->getPasswordResetTokenExpiresAt();

        return !empty($token) && $expiresAt && $expiresAt > new \DateTimeImmutable('now');
    }
}
