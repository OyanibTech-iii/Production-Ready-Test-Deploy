<?php

namespace App\Controller;

use App\Repository\CartRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use App\Service\StripePaymentService;
use App\Service\NotificationService;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripePaymentController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    #[Route('/stripe/checkout/session', name: 'app_stripe_checkout_session', methods: ['POST'])]
    public function createSession(
        Request $request,
        CartRepository $cartRepository,
        OrderService $orderService,
        StripePaymentService $stripePaymentService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $cart = $cartRepository->findOneByUserWithProducts($user);
        if (!$cart || $cart->getItems()->isEmpty()) {
            return new JsonResponse(['success' => false, 'message' => 'Your cart is empty'], 400);
        }

        $order = $orderService->createOrderFromCart($cart, 'stripe');

        $successUrl = $this->generateUrl('app_stripe_checkout_success', ['orderId' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->generateUrl('app_stripe_checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $payment = $stripePaymentService->createCheckoutSession($order, $successUrl, $cancelUrl);

        return new JsonResponse([
            'success' => true,
            'sessionId' => $payment->getStripeSessionId(),
        ]);
    }

    #[Route('/stripe/checkout/success', name: 'app_stripe_checkout_success', methods: ['GET'])]
    public function success(Request $request, EntityManagerInterface $entityManager, OrderService $orderService): Response
    {
        $orderId = $request->query->get('orderId');
        if ($orderId) {
            $order = $entityManager->getRepository(Order::class)->find($orderId);
            if ($order) {
                $orderService->completeOrder($order);
            }
        }

        return $this->render('user_page/checkout_success.html.twig');
    }

    #[Route('/stripe/checkout/cancel', name: 'app_stripe_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('user_page/checkout_cancel.html.twig');
    }
}