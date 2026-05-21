<?php
namespace App\Controller\Admin;

use App\Repository\{ProductRepository, UserRepository, StockRepository, OrderRepository};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\CourseRepository;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
   public function dashboard(ProductRepository $productRepository, UserRepository $userRepository, StockRepository $stockRepository, OrderRepository $orderRepository, CourseRepository $courseRepository): Response
    {
        // Get dashboard statistics
        $totalUsers = $userRepository->count([]);
        $recentUsers = $userRepository->findBy([], ['id' => 'DESC'], 6);
        $totalProducts = $productRepository->count([]);
        $totalCourses = $courseRepository->count([]);
        $recentProducts = $productRepository->findBy([], ['id' => 'DESC'], 6);
        $totalRevenue = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        $totalRevenue = (float) ($totalRevenue ?? 0); 

        // Dynamic User Growth Calculation
        $now = new \DateTimeImmutable();
        $startOfThisMonth = $now->modify('first day of this month')->setTime(0, 0);
        $startOfLastMonth = $now->modify('first day of last month')->setTime(0, 0);
        
        $usersThisMonth = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :start')
            ->setParameter('start', $startOfThisMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $usersLastMonth = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :start')
            ->andWhere('u.createdAt < :end')
            ->setParameter('start', $startOfLastMonth)
            ->setParameter('end', $startOfThisMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $userGrowthPercentage = 0;
        if ($usersLastMonth > 0) {
            $userGrowthPercentage = (($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100;
        } elseif ($usersThisMonth > 0) {
            $userGrowthPercentage = 100;
        }

        $activeGardens = 0;

        $maxDays = 90;
        $growth = [];
        $dates = [];
        $now = new \DateTimeImmutable();
        $startDate = $now->modify(sprintf('-%d days', $maxDays - 1))->setTime(0, 0);

        // Get all users created in the last 90 days
        $recentUsersCreated = $userRepository->createQueryBuilder('u')
            ->where('u.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Group by day in PHP
        $dailyCounts = [];
        foreach ($recentUsersCreated as $u) {
            $dayKey = $u->getCreatedAt()->format('Y-m-d');
            $dailyCounts[$dayKey] = ($dailyCounts[$dayKey] ?? 0) + 1;
        }

        // Get total users before start date
        $totalBefore = (int) $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt < :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getSingleScalarResult();

        $cumulativeCount = $totalBefore;
        for ($i = $maxDays - 1; $i >= 0; $i--) {
            $day = $now->modify(sprintf('-%d days', $i))->setTime(0, 0);
            $dateStr = $day->format('Y-m-d');
            
            $countToday = $dailyCounts[$dateStr] ?? 0;
            $cumulativeCount += $countToday;
            
            $growth[] = $cumulativeCount;
            $dates[] = $day->format('M d');
        }

        $stocks = $stockRepository->findAll();
        $stockTypeTotals = [];
        foreach ($stocks as $stock) {
            $type = $stock->getStockType() ?: 'Uncategorized';
            $quantity = $stock->getQuantity() ?? 0;
            if (!array_key_exists($type, $stockTypeTotals)) {
                $stockTypeTotals[$type] = 0;
            }
            $stockTypeTotals[$type] += $quantity;
        }
        $stockTypeLabels = array_keys($stockTypeTotals);
        $stockTypeData = array_values($stockTypeTotals);

        $allProducts = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.stocks', 's')
            ->select('p', 's')
            ->getQuery()
            ->getResult();

        $productStatusCounts = [
            'In stock' => 0,
            'Low stock' => 0,
            'Out of stock' => 0,
        ];
        foreach ($allProducts as $product) {
            $status = $product->getStockStatus();
            if (array_key_exists($status, $productStatusCounts)) {
                $productStatusCounts[$status]++;
            }
        }

        $productStatusLabels = array_keys($productStatusCounts);
        $productStatusData = array_values($productStatusCounts);

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'recent_users' => $recentUsers,
            'total_products' => $totalProducts,
            'recent_products' => $recentProducts,
            'total_revenue' => $totalRevenue,
            'active_gardens' => $activeGardens,
            'user' => $this->getUser(),
            'user_growth' => $growth,
            'user_growth_dates' => $dates,
            'user_growth_percentage' => $userGrowthPercentage,
            'stock_type_labels' => $stockTypeLabels,
            'stock_type_data' => $stockTypeData,
            'product_status_labels' => $productStatusLabels,
            'product_status_data' => $productStatusData,
            'total_courses' => $totalCourses,
        ]);
    }


 
    #[Route('/dashboard/authorize-download', name: 'app_admin_dashboard_authorize', methods: ['POST'])]
    public function authorizeDownload(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // CSRF header check
        $token = $request->headers->get('X-CSRF-TOKEN', '');
        if (!$this->isCsrfTokenValid('admin_dashboard_download', $token)) {
            return new JsonResponse(['authorized' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $password = $data['password'] ?? '';

        if (empty($password)) {
            return new JsonResponse(['authorized' => false, 'message' => 'Password is required'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['authorized' => false, 'message' => 'Not authenticated'], 401);
        }

        if ($passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['authorized' => true]);
        }

        return new JsonResponse(['authorized' => false, 'message' => 'Invalid password'], 403);
    }
}