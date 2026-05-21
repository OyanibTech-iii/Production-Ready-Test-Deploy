<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {}

    /**
     * Complete an order
     */
    public function completeOrder(Order $order): void
    {
        if ($order->getStatus() === Order::STATUS_COMPLETED) {
            return;
        }

        $order->setStatus(Order::STATUS_COMPLETED);

        // Decrement stock
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity() ?? 0;
            if ($product && $quantity > 0) {
                $product->decrementStock($quantity);
            }
        }

        // Create notification for admin user
        $adminUser = $this->userRepository->findOneBy(['roles' => 'ROLE_ADMIN']);
        if ($adminUser) {
            $this->notificationService->create(
                $adminUser,
                'Order Completed',
                'Order #' . $order->getId() . ' has been successfully completed.',
                'order',
                'high'
            );
        }

        $this->entityManager->flush();
    }

    /**
     * Convert a cart to an order
     */
    public function createOrderFromCart(Cart $cart, ?string $paymentMethod = null): Order
    {
        $user = $cart->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('Cart must have a user');
        }

        if ($cart->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Cart is empty');
        }

        // Calculate total
        $total = '0.00';
        foreach ($cart->getItems() as $cartItem) {
            $price = $cartItem->getProduct()->getPrice();
            if ($price === null) {
                throw new \InvalidArgumentException(sprintf('Product "%s" does not have a price.', $cartItem->getProduct()->getName()));
            }
            $subtotal = bcmul((string)$cartItem->getQuantity(), (string)$price, 2);
            $total = bcadd($total, $subtotal, 2);
        }

        // Create order
        $order = new Order();
        $order->setCustomer($user);
        $order->setPaymentMethod($paymentMethod);
        $order->setTotal($total);
        $order->setStatus(Order::STATUS_PENDING);

        // Create order items
        foreach ($cart->getItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setUnitPrice($cartItem->getProduct()->getPrice());
            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Convert a specific set of cart items to an order
     */
    public function createOrderFromCartItems(Cart $cart, array $cartItemIds, ?string $paymentMethod = null): Order
    {
        $user = $cart->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('Cart must have a user');
        }

        $items = [];
        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getId() !== null && in_array($cartItem->getId(), $cartItemIds, true)) {
                $items[] = $cartItem;
            }
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('No cart items selected for checkout.');
        }

        $total = '0.00';
        foreach ($items as $cartItem) {
            $price = $cartItem->getProduct()->getPrice();
            if ($price === null) {
                throw new \InvalidArgumentException(sprintf('Product "%s" does not have a price.', $cartItem->getProduct()->getName()));
            }
            $subtotal = bcmul((string)$cartItem->getQuantity(), (string)$price, 2);
            $total = bcadd($total, $subtotal, 2);
        }

        $order = new Order();
        $order->setCustomer($user);
        $order->setPaymentMethod($paymentMethod);
        $order->setTotal($total);
        $order->setStatus(Order::STATUS_PENDING);

        foreach ($items as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setUnitPrice($cartItem->getProduct()->getPrice());
            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Create reviews for an order
     */
    public function createReviewsForOrder(Order $order, array $reviewsData): void
    {
        $user = $order->getCustomer();
        if (!$user) {
            throw new \InvalidArgumentException('Order must have a customer');
        }

        foreach ($reviewsData as $reviewData) {
            $productId = $reviewData['productId'] ?? null;
            $rating = $reviewData['rating'] ?? null;
            $comment = $reviewData['comment'] ?? null;

            if (!$productId || !$rating) {
                continue; // Skip invalid reviews
            }

            // Find the order item for this product
            $orderItem = null;
            foreach ($order->getItems() as $item) {
                if ($item->getProduct()->getId() == $productId) {
                    $orderItem = $item;
                    break;
                }
            }

            if (!$orderItem) {
                continue; // Product not in order
            }

            // Create review
            $review = new Review();
            $review->setUser($user);
            $review->setProduct($orderItem->getProduct());
            $review->setOrder($order);
            $review->setRating((int)$rating);
            if ($comment) {
                $review->setComment(trim($comment));
            }

            $this->entityManager->persist($review);
        }

        $this->entityManager->flush();
    }

    /**
     * Create order with reviews in one transaction
     */
    public function createOrderWithReviews(Cart $cart, ?string $paymentMethod = null, array $reviewsData = [], array $cartItemIds = []): Order
    {
        if (empty($cartItemIds)) {
            $order = $this->createOrderFromCart($cart, $paymentMethod);
        } else {
            $order = $this->createOrderFromCartItems($cart, $cartItemIds, $paymentMethod);
        }

        $this->createReviewsForOrder($order, $reviewsData);
        return $order;
    }
}