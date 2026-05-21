<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use App\Service\ForgotPasswordEmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/password')]
class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private readonly ForgotPasswordEmailService $passwordEmailService,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Show forgot password form.
     */
    #[Route('/forgot', name: 'app_forgot_password', methods: ['GET'])]
    public function forgotPassword(): Response
    {
        return $this->render('security/forgot_password.html.twig');
    }

    /**
     * Handle forgot password form submission.
     * 
     * Sends password reset email if user exists.
     * Always returns success message for security (prevents email enumeration).
     * GET requests are redirected to the form page.
     */
    #[Route('/forgot-request', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function forgotPasswordRequest(Request $request): Response
    {
        // Redirect GET requests to the form
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('app_forgot_password');
        }

        // Get email from request
        $email = trim($request->get('email', ''));

        // Validate email format
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('warning', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Find user by email
        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Security: Always show success message to prevent email enumeration
        if ($user === null) {
            $this->addFlash('success', 'If an account exists with that email, you will receive a password reset link shortly.');
            return $this->render('security/forgot_password_confirmation.html.twig');
        }

        try {
            // Generate reset token
            $token = $this->passwordEmailService->createPasswordResetToken($user);

            // Create reset URL
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], true);
            if (strpos($resetUrl, 'http') !== 0) {
                $resetUrl = $request->getSchemeAndHttpHost() . $resetUrl;
            }

            // Send reset email
            $this->passwordEmailService->sendPasswordResetEmail($user, $resetUrl);

            $this->addFlash('success', 'If an account exists with that email, you will receive a password reset link shortly.');
            return $this->render('security/forgot_password_confirmation.html.twig');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while processing your request. Please try again.');
            return $this->redirectToRoute('app_forgot_password');
        }
    }

    /**
     * Show reset password form.
     * Validates token before showing form.
     */
    #[Route('/reset/{token}', name: 'app_reset_password', methods: ['GET'])]
    public function resetPassword(string $token): Response
    {
        // Verify token and get user
        $user = $this->passwordEmailService->verifyPasswordResetToken($token);

        if ($user === null) {
            $this->addFlash('error', 'This password reset link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Handle reset password form submission.
     * 
     * Validates token, updates password, and clears token.
     */
    #[Route('/reset-request', name: 'app_reset_password_request', methods: ['POST'])]
    public function resetPasswordRequest(Request $request): Response
    {
        $token = trim($request->get('token', ''));

        // Verify token
        $user = $this->passwordEmailService->verifyPasswordResetToken($token);

        if ($user === null) {
            $this->addFlash('error', 'This password reset link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Get new password from request
        $newPassword = $request->get('password', '');
        $confirmPassword = $request->get('password_confirm', '');

        // Validate password requirements
        if (empty($newPassword) || strlen($newPassword) < 8) {
            $this->addFlash('error', 'Password must be at least 8 characters long.');
            return $this->redirectToRoute('app_reset_password', ['token' => $token]);
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Passwords do not match.');
            return $this->redirectToRoute('app_reset_password', ['token' => $token]);
        }

        try {
            // Update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Clear reset token
            $this->passwordEmailService->clearPasswordResetToken($user);

            $this->addFlash('success', 'Your password has been reset successfully. You can now log in.');
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while resetting your password. Please try again.');
            return $this->redirectToRoute('app_reset_password', ['token' => $token]);
        }
    }
}
