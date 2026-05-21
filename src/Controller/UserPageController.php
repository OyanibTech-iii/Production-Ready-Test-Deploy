<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Cart;
use App\Entity\Enrollment;
use App\Entity\Quiz;
use App\Entity\QuizAttempt;
use App\Entity\Certificate;
use App\Entity\Lesson;
use App\Enum\Status;
use App\Repository\CartRepository;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\ProductRepository;
use App\Repository\QuizRepository;
use App\Repository\QuizAttemptRepository;
use App\Repository\CertificateRepository;
use App\Service\CartService;
use App\Service\UserStatsService;
use App\Service\OrderService;
use App\Service\StripePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/userpage')]
#[IsGranted('ROLE_USER')]
final class UserPageController extends AbstractController
{
    #[Route('/product/{id}/details', name: 'app_user_product_details', methods: ['GET'])]
    public function getProductDetails(\App\Entity\Product $product, \Symfony\Component\Asset\Packages $assetManager): JsonResponse
    {
        $reviews = [];
        foreach ($product->getReviews() as $review) {
            $reviews[] = [
                'user' => $review->getUser() ? $review->getUser()->getFullName() : 'Anonymous',
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'date' => $review->getCreatedAt()->format('M d, Y'),
            ];
        }

        return new JsonResponse([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'image' => $product->getImagePath() ? (str_starts_with($product->getImagePath(), 'http') ? $product->getImagePath() : $assetManager->getUrl($product->getImagePath())) : null,
            'averageRating' => $product->getAverageRating(),
            'reviewsCount' => $product->getReviews()->count(),
            'reviews' => $reviews,
            'available' => ($product->isAvailable() ?? true) && $product->getCurrentStockQuantity() > 0,
            'stockStatus' => $product->getStockStatus(),
        ]);
    }

    #[Route('/shop/product/{id}', name: 'app_user_shop_product_details', methods: ['GET'])]
    public function productDetailsShop(\App\Entity\Product $product): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        return $this->render('user_page/detailsshop.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/home', name: 'app_user_home')]
    public function home(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $products = $productRepository->findAllWithStocks();
        return $this->render('user_page/user_home.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/', name: 'app_user_page')]
    public function index(ProductRepository $productRepository, UserStatsService $userStatsService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $ownedProducts = $productRepository->findBy(['owner' => $user], ['id' => 'DESC'], 6);

        // Get all stats from the centralized service
        $stats = $userStatsService->getDetailedStats($user);

        return $this->render('user_page/index.html.twig', [
            'controller_name' => 'UserPageController',
            'user' => $user,
            'ownedProducts' => $ownedProducts,
            'stats' => $stats,
            'isStaff' => $stats['isStaff'],
        ]);
    }

    #[Route('/profile', name: 'app_user_profile')]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user_page/profile.html.twig', [
            'user' => $user,
            'isStaff' => in_array('ROLE_STAFF', $user->getRoles(), true),
        ]);
    }

    #[Route('/courses', name: 'app_user_courses')]
    public function courses(
        CourseRepository $courseRepository,
        EnrollmentRepository $enrollmentRepository,
        QuizRepository $quizRepository,
        QuizAttemptRepository $quizAttemptRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $allCourses = $courseRepository->findBy([], ['id' => 'DESC']);

        // Get all enrollments for the user
        $enrollments = $enrollmentRepository->findBy(['user' => $user]);

        // Create a map of course IDs to enrollments
        $enrollmentMap = [];
        foreach ($enrollments as $enrollment) {
            $enrollmentMap[$enrollment->getCourse()->getId()] = $enrollment;
        }

        /** @var array<int, 'not_enrolled'|'in_progress'|'completed'> */
        $courseCardStatus = [];
        foreach ($allCourses as $course) {
            $courseId = (int) $course->getId();
            $enrollment = $enrollmentMap[$courseId] ?? null;

            if (!$enrollment) {
                $courseCardStatus[$courseId] = 'not_enrolled';

                continue;
            }

            $quiz = $quizRepository->findOneBy(['course' => $course]);
            $quizPassed = false;
            if ($quiz) {
                $quizPassed = $quizAttemptRepository->count([
                    'user' => $user,
                    'quiz' => $quiz,
                    'isPassed' => true,
                ]) > 0;
            }

            $courseCardStatus[$courseId] = $quizPassed ? 'completed' : 'in_progress';
        }

        return $this->render('user_page/user_courses.html.twig', [
            'courses' => $allCourses,
            'enrollmentMap' => $enrollmentMap,
            'courseCardStatus' => $courseCardStatus,
        ]);
    }

    #[Route('/courses/{id}', name: 'app_user_course_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showCourse(Course $course, EnrollmentRepository $enrollmentRepository, QuizRepository $quizRepository, QuizAttemptRepository $quizAttemptRepository, CertificateRepository $certificateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        // Get enrollment for this course
        $enrollment = $enrollmentRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        // Get quiz for this course
        $quiz = $quizRepository->findOneBy(['course' => $course]);

        // Get latest quiz attempt for this user and quiz
        $quizAttempt = null;
        $certificate = null;
        $quizPassed = false;

        if ($quiz) {
            $quizAttempt = $quizAttemptRepository->findOneBy(
                ['user' => $user, 'quiz' => $quiz],
                ['attemptedAt' => 'DESC']
            );
            $quizPassed = $quizAttemptRepository->count([
                'user' => $user,
                'quiz' => $quiz,
                'isPassed' => true,
            ]) > 0;
        }

        // Get certificate for this user and course
        $certificate = $certificateRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        $lessons = $course->getLessons()->toArray();
        usort($lessons, fn(Lesson $a, Lesson $b) => $a->getId() <=> $b->getId());

        return $this->render('user_page/course_show.html.twig', [
            'course' => $course,
            'lessons' => $lessons,
            'enrollment' => $enrollment,
            'quizAttempt' => $quizAttempt,
            'certificate' => $certificate,
            'quizPassed' => $quizPassed,
        ]);
    }

    #[Route('/courses/air-layering', name: 'app_user_course_airlayering')]
    public function courseAirLayering(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('user_page/course_airlayering.html.twig');
    }

    #[Route('/courses/{courseId}/take', name: 'app_user_course_take', methods: ['POST'], requirements: ['courseId' => '\d+'])]
    public function takeCourse(int $courseId, CourseRepository $courseRepository, EnrollmentRepository $enrollmentRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $course = $courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        // Find or create enrollment
        $enrollment = $enrollmentRepository->findOneBy([
            'user' => $user,
            'course' => $course,
        ]);

        if ($enrollment) {
            if ($enrollment->getStatus() === Status::PENDING) {
                $enrollment->setStatus(Status::ACTIVE);
            }
            // Unlocks quiz/materials on the course page (completion is tracked via quiz pass).
            $enrollment->setCourseTaken(true);
        } else {
            // If someone submits the "take" action without enrolling first,
            // create the enrollment so the rest of the flow works.
            $now = new \DateTimeImmutable();
            $enrollment = new Enrollment();
            $enrollment->setUser($user);
            $enrollment->setCourse($course);
            $enrollment->setStatus(Status::ACTIVE);
            $enrollment->setEnrolledAt($now);
            $enrollment->setCompletedAt(new \DateTimeImmutable());
            $enrollment->setCourseTaken(true);
            $entityManager->persist($enrollment);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_user_course_show', ['id' => $courseId]);
    }

    #[Route('/courses/{courseId}/enroll', name: 'app_user_course_enroll', methods: ['POST'], requirements: ['courseId' => '\d+'])]
    public function enrollCourse(int $courseId, CourseRepository $courseRepository, EnrollmentRepository $enrollmentRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $course = $courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $isFree = $course->getPrice() !== null && (float) $course->getPrice() <= 0.0;
        if (!$isFree) {
            throw $this->createAccessDeniedException('You can only enroll in free courses.');
        }

        $enrollment = $enrollmentRepository->findOneBy([
            'user' => $user,
            'course' => $course,
        ]);

        if (!$enrollment) {
            $now = new \DateTimeImmutable();
            $enrollment = new Enrollment();
            $enrollment->setUser($user);
            $enrollment->setCourse($course);
            $enrollment->setStatus(Status::ACTIVE);
            $enrollment->setEnrolledAt($now);
            $enrollment->setCompletedAt(new \DateTimeImmutable());
            $enrollment->setCourseTaken(false);
            $entityManager->persist($enrollment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_course_show', ['id' => $courseId]);
    }

    #[Route('/courses/{courseId}/quiz', name: 'app_user_course_quiz', methods: ['GET'], requirements: ['courseId' => '\d+'])]
    public function courseQuiz(
        int $courseId,
        CourseRepository $courseRepository,
        QuizRepository $quizRepository,
        EnrollmentRepository $enrollmentRepository,
        QuizAttemptRepository $quizAttemptRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $course = $courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        // Check if user is enrolled
        $enrollment = $enrollmentRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$enrollment) {
            throw $this->createAccessDeniedException('You are not enrolled in this course');
        }

        $quiz = $quizRepository->findOneForCourseWithQuestionsAndChoices($course);
        if (!$quiz) {
            throw $this->createNotFoundException('Quiz not found for this course');
        }

        $alreadyPassed = $quizAttemptRepository->count([
            'user' => $user,
            'quiz' => $quiz,
            'isPassed' => true,
        ]) > 0;
        if ($alreadyPassed) {
            return $this->redirectToRoute('app_user_course_show', ['id' => $courseId]);
        }

        return $this->render('user_page/course_quiz.html.twig', [
            'course' => $course,
            'quiz' => $quiz,
            'enrollment' => $enrollment,
        ]);
    }

    #[Route('/courses/{courseId}/quiz/submit', name: 'app_user_quiz_submit', methods: ['POST'], requirements: ['courseId' => '\d+'])]
    public function submitQuiz(int $courseId, Request $request, CourseRepository $courseRepository, QuizRepository $quizRepository, EnrollmentRepository $enrollmentRepository, QuizAttemptRepository $quizAttemptRepository, CertificateRepository $certificateRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $course = $courseRepository->find($courseId);
        if (!$course) {
            return new JsonResponse(['success' => false, 'message' => 'Course not found'], 404);
        }

        // Check enrollment
        $enrollment = $enrollmentRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$enrollment) {
            return new JsonResponse(['success' => false, 'message' => 'Not enrolled in course'], 403);
        }

        $quiz = $quizRepository->findOneForCourseWithQuestionsAndChoices($course);
        if (!$quiz) {
            return new JsonResponse(['success' => false, 'message' => 'Quiz not found'], 404);
        }

        if (
            $quizAttemptRepository->count([
                'user' => $user,
                'quiz' => $quiz,
                'isPassed' => true,
            ]) > 0
        ) {
            return new JsonResponse(['success' => false, 'message' => 'You have already passed this quiz.'], 403);
        }

        // Get answers from request (JSON object keys are strings; normalize for lookup)
        $data = json_decode($request->getContent(), true);
        $rawAnswers = $data['answers'] ?? [];
        $answers = [];
        foreach ($rawAnswers as $questionIdKey => $choiceId) {
            $answers[(int) $questionIdKey] = $choiceId;
        }

        // Calculate score: % = earned points / max points (supports per-question points)
        $correctAnswers = 0;
        $questions = $quiz->getQuestions();
        $totalQuestions = $questions->count();
        $maxPoints = 0;
        $earnedPoints = 0;

        foreach ($questions as $question) {
            $questionId = $question->getId();
            $points = $question->getPoints() ?? 0;
            $maxPoints += $points;

            if (!isset($answers[$questionId])) {
                continue;
            }

            $selectedAnswerId = (int) $answers[$questionId];

            foreach ($question->getAnswerChoices() as $choice) {
                if ((int) $choice->getId() === $selectedAnswerId && $choice->isCorrect()) {
                    ++$correctAnswers;
                    $earnedPoints += $points;
                    break;
                }
            }
        }

        if ($maxPoints > 0) {
            $score = ($earnedPoints / $maxPoints) * 100;
        } elseif ($totalQuestions > 0) {
            $score = ($correctAnswers / $totalQuestions) * 100;
        } else {
            $score = 0;
        }
        $isPassed = $score >= $quiz->getPassingScore();

        // Create quiz attempt
        $quizAttempt = new QuizAttempt();
        $quizAttempt->setUser($user);
        $quizAttempt->setQuiz($quiz);
        $quizAttempt->setScore($score);
        $quizAttempt->setTotalQuestions($totalQuestions);
        $quizAttempt->setCorrectAnswers($correctAnswers);
        $quizAttempt->setIsPassed($isPassed);
        $quizAttempt->setAttemptedAt(new \DateTimeImmutable());

        $entityManager->persist($quizAttempt);

        // If passed, create certificate
        if ($isPassed) {
            // Check if certificate already exists for this user and course
            $existingCert = $certificateRepository->findOneBy([
                'user' => $user,
                'course' => $course
            ]);

            if (!$existingCert) {
                $randomString = bin2hex(random_bytes(12));
                $defaultCode = 'GrowficoOfficial' . $randomString;

                $certificate = new Certificate();
                $certificate->setUser($user);
                $certificate->setCourse($course);
                $certificate->setCertificateCode($defaultCode);
                $certificate->setIssuedAt(new \DateTimeImmutable());
                $certificate->setQuizAttempt($quizAttempt);

                $entityManager->persist($certificate);
            }

            // Mark course as taken
            $enrollment->setCourseTaken(true);
            $enrollment->setStatus(Status::COMPLETED);
            $enrollment->setCompletedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'isPassed' => $isPassed,
            'score' => round($score, 2),
            'correctAnswers' => $correctAnswers,
            'totalQuestions' => $totalQuestions,
            'passingScore' => $quiz->getPassingScore(),
            'certificateId' => $isPassed ? $course->getId() : null,
        ]);
    }

    #[Route('/certificate/{courseId}', name: 'app_user_certificate', methods: ['GET'], requirements: ['courseId' => '\d+'])]
    public function viewCertificate(int $courseId, CourseRepository $courseRepository, CertificateRepository $certificateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $course = $courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $certificate = $certificateRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$certificate) {
            throw $this->createNotFoundException('Certificate not found. Please complete the quiz first.');
        }

        return $this->render('user_page/certificate.html.twig', [
            'course' => $course,
            'certificate' => $certificate,
            'user' => $user,
        ]);
    }

    #[Route('/certificate/{courseId}/download', name: 'app_user_certificate_download', methods: ['GET'], requirements: ['courseId' => '\d+'])]
    public function downloadCertificate(int $courseId, CourseRepository $courseRepository, CertificateRepository $certificateRepository, QuizRepository $quizRepository, QuizAttemptRepository $quizAttemptRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $course = $courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $quiz = $quizRepository->findOneBy(['course' => $course]);
        if (
            !$quiz || $quizAttemptRepository->count([
                'user' => $user,
                'quiz' => $quiz,
                'isPassed' => true,
            ]) < 1
        ) {
            throw $this->createAccessDeniedException('You must pass the quiz before downloading your certificate.');
        }

        $certificate = $certificateRepository->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$certificate) {
            throw $this->createNotFoundException('Certificate not found');
        }

        // TODO: Implement PDF generation and download
        // For now, redirect to the certificate view page
        return $this->redirectToRoute('app_user_certificate', ['courseId' => $courseId]);
    }

    #[Route('/cart', name: 'app_user_cart', methods: ['GET'])]
    public function cart(CartRepository $cartRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartRepository->findOneByUserWithProducts($user);

        return $this->render('user_page/cart.html.twig', [
            'cart' => $cart,
            'stripe_publishable_key' => $this->getParameter('stripe_public_key'),
        ]);
    }

    #[Route('/cart/summary', name: 'app_user_cart_summary', methods: ['GET'])]
    public function cartSummary(CartService $cartService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'count' => $cartService->getTotalQuantityForUser($user),
        ]);
    }

    #[Route('/cart/items', name: 'app_user_cart_add_item', methods: ['POST'])]
    public function cartAddItem(Request $request, CartService $cartService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            $data = [];
        }

        $productId = isset($data['productId']) ? (int) $data['productId'] : 0;
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 1;
        $paymentMethod = isset($data['paymentMethod']) ? trim((string) $data['paymentMethod']) : null;
        if ($paymentMethod === '') {
            $paymentMethod = null;
        }

        try {
            $cart = $cartService->getOrCreateCart($user);
            $cartService->addProduct($cart, $productId, max(1, $quantity), $paymentMethod);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Added to cart',
            'itemCount' => $cartService->getTotalQuantityForUser($user),
        ]);
    }
    #[Route('/cart/items/{id}', name: 'app_user_cart_remove_item', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function removeCartItem(int $id, CartRepository $cartRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        // Find the user's cart
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return new JsonResponse(['success' => false, 'message' => 'Cart not found'], 404);
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $id) {
                $cart->removeItem($item);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Item removed',
                    'newTotal' => $cart->getTotalItemQuantity()
                ]);
            }
        }

        return new JsonResponse(['success' => false, 'message' => 'Item not found in your cart'], 404);
    }

    #[Route('/cart/items/{id}/quantity', name: 'app_user_cart_update_quantity', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateCartItemQuantity(int $id, Request $request, CartRepository $cartRepository, CartService $cartService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : null;

        if ($quantity === null) {
            return new JsonResponse(['success' => false, 'message' => 'Quantity is required'], 400);
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return new JsonResponse(['success' => false, 'message' => 'Cart not found'], 404);
        }

        try {
            $cartService->updateItemQuantity($cart, $id, $quantity);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Quantity updated',
            'newTotal' => $cart->getTotalItemQuantity(),
            'newSubtotal' => $cart->getSubtotal()
        ]);
    }

    #[Route('/cart/items/fragment', name: 'app_user_cart_items_fragment', methods: ['GET'])]
    public function cartItemsFragment(CartRepository $cartRepository, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        // Get user's cart items
        $cart = $cartRepository->findOneByUserWithProducts($user);
        $products = $cart ? $cart->getItems()->toArray() : [];

        // Check if user is staff
        $isStaff = in_array('ROLE_STAFF', $user->getRoles(), true);

        return $this->render('user_page/_cart_items_content.html.twig', [
            'products' => $products,
            'isStaff' => $isStaff,
        ]);
    }

    #[Route('/cart/checkout', name: 'app_user_cart_checkout', methods: ['POST'])]
    public function checkout(Request $request, CartRepository $cartRepository, OrderService $orderService, StripePaymentService $stripePaymentService, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $cart = $cartRepository->findOneByUserWithProducts($user);
        if (!$cart || $cart->getItems()->isEmpty()) {
            return new JsonResponse(['success' => false, 'message' => 'Your cart is empty'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            $data = [];
        }

        $paymentMethod = $data['paymentMethod'] ?? null;
        $reviewsData = $data['reviews'] ?? [];
        $cartItemIds = [];
        if (isset($data['itemIds']) && is_array($data['itemIds'])) {
            $cartItemIds = array_map('intval', $data['itemIds']);
        }

        try {
            if ($paymentMethod === 'stripe') {
                // For Stripe, create order first, then create checkout session
                $order = $orderService->createOrderWithReviews($cart, $paymentMethod, $reviewsData, $cartItemIds);

                $successUrl = $this->generateUrl('app_stripe_checkout_success', ['orderId' => $order->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
                $cancelUrl = $this->generateUrl('app_stripe_checkout_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

                $payment = $stripePaymentService->createCheckoutSession($order, $successUrl, $cancelUrl);

                // Clear ordered items from the cart after creating Stripe session
                foreach ($cart->getItems() as $item) {
                    if (empty($cartItemIds) || in_array($item->getId(), $cartItemIds, true)) {
                        $entityManager->remove($item);
                    }
                }
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Redirecting to Stripe checkout...',
                    'redirect' => true,
                    'sessionId' => $payment->getStripeSessionId(),
                ]);
            } else {
                // For other payment methods (like COD), create order directly
                $order = $orderService->createOrderWithReviews($cart, $paymentMethod, $reviewsData, $cartItemIds);

                // Clear ordered items from the cart after successful order
                foreach ($cart->getItems() as $item) {
                    if (empty($cartItemIds) || in_array($item->getId(), $cartItemIds, true)) {
                        $entityManager->remove($item);
                    }
                }
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Order placed successfully!',
                    'orderId' => $order->getId(),
                ]);
            }
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()], 500);
        }
    }
}
