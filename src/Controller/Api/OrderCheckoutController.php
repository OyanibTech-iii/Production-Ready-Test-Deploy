<?php

namespace App\Controller\Api;

use App\Repository\CartRepository;
use App\Service\OrderService;
use App\Service\StripePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderCheckoutController extends AbstractController
{
    public function __construct(
        private CartRepository $cartRepository,
        private OrderService $orderService,
        private StripePaymentService $stripePaymentService,
        private string $stripePublicKey
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $cart = $this->cartRepository->findOneByUserWithProducts($user);
        if (!$cart || $cart->getItems()->isEmpty()) {
            return new JsonResponse(['error' => 'Cart is empty'], 400);
        }

        try {
            $order = $this->orderService->createOrderFromCart($cart, 'stripe');

            // Success and cancel URLs (mobile might handle these differently, but Stripe requires them)
            $successUrl = $this->generateUrl('app_stripe_checkout_success', ['orderId' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('app_stripe_checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $payment = $this->stripePaymentService->createCheckoutSession($order, $successUrl, $cancelUrl);

            return new JsonResponse([
                'orderId' => $order->getId(),
                'stripeSessionId' => $payment->getStripeSessionId(),
                'stripePublicKey' => $this->stripePublicKey,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
