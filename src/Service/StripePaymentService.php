<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;

class StripePaymentService
{
    public function __construct(
        private StripeClient $stripeClient,
        private EntityManagerInterface $entityManager,
        private PaymentRepository $paymentRepository
    ) {}

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): Payment
    {
        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'php',
                    'product_data' => [
                        'name' => $product->getName(),
                        'description' => $product->getDescription() ?: '',
                    ],
                    'unit_amount' => (int) round($product->getPrice() * 100),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        $session = $this->stripeClient->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->getCustomer()->getEmail(),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => $order->getId(),
            ],
        ]);

        $payment = new Payment();
        $payment->setOrderId($order);
        $payment->setStripeSessionId($session->id);
        $payment->setStripePaymentIntentId($session->payment_intent);
        $payment->setAmount($order->getTotal());
        $payment->setCurrency('php');
        $payment->setStatus('pending');
        $payment->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}