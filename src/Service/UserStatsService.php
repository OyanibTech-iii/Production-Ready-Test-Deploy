<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\CertificateRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\ProductRepository;
use App\Repository\QuizAttemptRepository;
final class UserStatsService
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly ProductRepository $productRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly CertificateRepository $certificateRepository,
        private readonly QuizAttemptRepository $quizAttemptRepository,
    ) {}

    /**
     * Get all stats for a user
     * 
     * @return array<string, int>
     */
    public function getUserStats(User $user): array
    {
        return [
            'ownedProducts' => $this->getOwnedProductsCount($user),
            'totalProducts' => $this->getTotalProductsCount(),
            'enrolledCourses' => $this->getEnrolledCoursesCount($user),
            'certificates' => $this->getCertificatesCount($user),
            'cartCount' => $this->getCartItemsCount($user),
        ];
    }

    /**
     * Get count of products owned by the user
     */
    public function getOwnedProductsCount(User $user): int
    {
        return $this->productRepository->count(['owner' => $user]);
    }

    /**
     * Get total count of all products in the system
     */
    public function getTotalProductsCount(): int
    {
        return $this->productRepository->count([]);
    }

    /**
     * Get count of courses the user is enrolled in (from actual Enrollment records)
     * This counts unique courses from enrollment records, not the ManyToMany relationship
     */
    public function getEnrolledCoursesCount(User $user): int
    {
        return $this->enrollmentRepository->count(['user' => $user]);
    }

    /**
     * Get count of certificates the user has earned (from actual Certificate records)
     */
    public function getCertificatesCount(User $user): int
    {
        return $this->certificateRepository->count(['user' => $user]);
    }

    /**
     * Get count of items in the user's cart
     */
    public function getCartItemsCount(User $user): int
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        
        if (!$cart) {
            return 0;
        }

        return count($cart->getItems());
    }

    /**
     * Get course progress (enrolled vs completed)
     */
    public function getCourseProgress(User $user): array
    {
        $enrolled = $this->enrollmentRepository->count(['user' => $user]);
        $completed = $this->enrollmentRepository->count(['user' => $user, 'status' => \App\Enum\Status::COMPLETED]);

        return [
            'enrolled' => $enrolled - $completed,
            'completed' => $completed
        ];
    }

    /**
     * Get quiz performance (passed vs failed)
     */
    public function getQuizPerformance(User $user): array
    {
        $passed = $this->quizAttemptRepository->count(['user' => $user, 'isPassed' => true]);
        $failed = $this->quizAttemptRepository->count(['user' => $user, 'isPassed' => false]);

        return [
            'passed' => $passed,
            'failed' => $failed
        ];
    }

    /**
     * Get detailed stats for a user with additional breakdown
     * 
     * @return array<string, mixed>
     */
    public function getDetailedStats(User $user): array
    {
        $basicStats = $this->getUserStats($user);
        
        return array_merge($basicStats, [
            'isStaff' => in_array('ROLE_STAFF', $user->getRoles(), true),
            'isActive' => $user->isActive(),
            'courseProgress' => $this->getCourseProgress($user),
            'quizPerformance' => $this->getQuizPerformance($user),
        ]);
    }
}
