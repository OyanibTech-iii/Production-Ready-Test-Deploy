<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['order'];
    }

    public function load(ObjectManager $manager): void
    {
        $userRepo = $manager->getRepository(User::class);
        $productRepo = $manager->getRepository(Product::class);

        $customers = $userRepo->findAll();
        $products = $productRepo->findAll();

        if (empty($customers) || empty($products)) {
            return;
        }

        $productList = array_values($products);
        for ($i = 0; $i < 8; $i++) {
            $order = new Order();
            $order->setCustomer($customers[array_rand($customers)]);
            $order->setStatus(Order::STATUS_COMPLETED);
            $order->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', $i * 2)));

            $total = '0.00';
            shuffle($productList);
            $numItems = random_int(1, min(3, count($productList)));
            $selectedProducts = array_slice($productList, 0, $numItems);
            foreach ($selectedProducts as $product) {
                $qty = random_int(1, 3);
                $unitPrice = $product->getPrice();
                $item = new OrderItem();
                $item->setProduct($product);
                $item->setQuantity($qty);
                $item->setUnitPrice($unitPrice);
                $order->addItem($item);
                $total = bcadd($total, bcmul((string) $qty, (string) $unitPrice, 2), 2);
            }
            $order->setTotal($total);
            $manager->persist($order);
        }

        $manager->flush();
    }
}
