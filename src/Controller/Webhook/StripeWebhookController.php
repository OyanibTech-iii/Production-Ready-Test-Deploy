<?php

namespace App\Controller\Webhook;

use App\Entity\Order;
use App\Entity\Payment;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderService $orderService,
        private string $stripeWebhookSecret
    ) {}

    #[Route('/webhooks/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return new Response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return new Response('Invalid signature', 400);
        }

        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            if ($orderId) {
                $order = $this->entityManager->getRepository(Order::class)->find($orderId);
                if ($order) {
                    // Update payment info if available
                    $payment = $this->entityManager->getRepository(Payment::class)->findOneBy(['stripeSessionId' => $session->id]);
                    if ($payment) {
                        $payment->setStatus('paid');
                        $payment->setStripePaymentIntentId($session->payment_intent);
                    }

                    // Complete the order
                    $this->orderService->completeOrder($order);
                }
            }
        }

        return new Response('Webhook Handled', 200);
    }
}
