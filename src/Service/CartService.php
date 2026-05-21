<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CartService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository,
        private ProductRepository $productRepository,
    ) {
    }

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $this->cartRepository->findOneByUser($user);
        if ($cart instanceof Cart) {
            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);
        $user->setCart($cart);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    /**
     * Adds quantity to an existing line or creates one row per (cart, product).
     *
     * @throws \InvalidArgumentException When product missing or unavailable
     */
    public function addProduct(Cart $cart, int $productId, int $quantity, ?string $paymentMethod): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        $product = $this->productRepository->find($productId);
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Product not found.');
        }

        if ($product->isAvailable() !== true) {
            throw new \InvalidArgumentException('This product is not available.');
        }

        $availableStock = $product->getCurrentStockQuantity();
        if ($availableStock <= 0) {
            throw new \InvalidArgumentException('This product is out of stock.');
        }

        $item = null;
        foreach ($cart->getItems() as $existing) {
            if ($existing->getProduct()?->getId() === $product->getId()) {
                $item = $existing;
                break;
            }
        }

        $newQuantity = $quantity;
        if ($item instanceof CartItem) {
            $newQuantity = $item->getQuantity() + $quantity;
        }

        if ($newQuantity > $availableStock) {
            throw new \InvalidArgumentException(sprintf('Only %d item(s) are available.', $availableStock));
        }

        if ($item instanceof CartItem) {
            $item->setQuantity($newQuantity);
            if ($paymentMethod !== null && $paymentMethod !== '') {
                $item->setPaymentMethod($paymentMethod);
            }
        } else {
            $item = new CartItem();
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $item->setPaymentMethod($paymentMethod);
            $cart->addItem($item);
        }

        $cart->touch();
        $this->entityManager->flush();
    }

    public function getTotalQuantityForUser(User $user): int
    {
        $cart = $this->cartRepository->findOneByUser($user);
        if (!$cart instanceof Cart) {
            return 0;
        }

        return $cart->getTotalItemQuantity();
    }

    public function updateItemQuantity(Cart $cart, int $itemId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        $item = null;
        foreach ($cart->getItems() as $existing) {
            if ($existing->getId() === $itemId) {
                $item = $existing;
                break;
            }
        }

        if (!$item instanceof CartItem) {
            throw new \InvalidArgumentException('Cart item not found.');
        }

        $product = $item->getProduct();
        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Product not found.');
        }

        $availableStock = $product->getCurrentStockQuantity();
        if ($quantity > $availableStock) {
            throw new \InvalidArgumentException(sprintf('Only %d item(s) are available.', $availableStock));
        }

        $item->setQuantity($quantity);
        $cart->touch();
        $this->entityManager->flush();
    }
}
