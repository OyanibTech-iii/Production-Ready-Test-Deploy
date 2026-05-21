<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Stock;
use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StockFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $locations = $manager->getRepository(Location::class)->findAll();
        
        if (empty($locations)) {
            // This shouldn't happen if LocationFixtures ran
            return;
        }

        $productsData = [
            'Bread fruit' => 'seedlings',
            'Black Sapote' => 'seedlings',
            'Cashew' => 'seedlings',
            'Kamias' => 'seedlings',
            'Mansanitas' => 'seedlings',
            'Señiorita' => 'seedlings',
            'Mabolo' => 'seedlings',
            'Spiral' => 'topiary',
            'Duranta' => 'topiary',
            'Fukien Tea' => 'topiary',
            'Privet' => 'topiary',
            'Murraya' => 'topiary',
            'Aggregate' => 'base',
            'Black Coal Sand' => 'base',
            'Cone' => 'topiary',
            'Box  Murraya' => 'topiary',
            'Carbonize Rice Hull' => 'base',
            'Lamp' => 'topiary',
            'Vermi Cast' => 'base',
            'Canistel' => 'seedlings',
            'Limestones' => 'steps',
            'Sandstone' => 'steps',
            'Slate' => 'steps',
            'Sand Stone' => 'steps',
            'Basalt' => 'steps',
            'Granite' => 'steps',
            'Red Clay Bricks' => 'bricks',
            'Concrete Paver' => 'bricks',
            'Biege Sandstone' => 'bricks',
            'Black Basalt' => 'bricks',
            'Basalt Paver' => 'bricks',
            'Rustic Patio' => 'bricks',
            'Cobblestone' => 'bricks',
            'Zoysia' => 'grass',
            'Carpet' => 'grass',
            'Centipede' => 'grass',
            'Buffalo' => 'grass',
            'Saw Dust' => 'base',
            'Cocopit' => 'base',
            'River sand' => 'base',
        ];

        foreach ($productsData as $productName => $stockType) {
            $product = $manager->getRepository(Product::class)->findOneBy(['name' => $productName]);
            
            if (!$product) {
                continue;
            }

            // Check if stock already exists for this product to avoid duplicates
            $existingStock = $manager->getRepository(Stock::class)->createQueryBuilder('s')
                ->join('s.products', 'p')
                ->where('p.id = :productId')
                ->setParameter('productId', $product->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingStock) {
                continue;
            }

            $stock = new Stock();
            $stock->setQuantity(100);
            $stock->setMinimumQuantity(10);
            $stock->setMaximumQuantity(400);
            $stock->setStockType($stockType);
            $stock->setOwner(null);
            $stock->setCreatedAt(new \DateTime());
            $stock->setUpdatedAt(new \DateTime());
            
            // Randomize location
            $randomLocation = $locations[array_rand($locations)];
            $stock->setLocationRel($randomLocation);
            
            // Link product
            $stock->addProduct($product);

            $manager->persist($stock);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
            LocationFixtures::class,
        ];
    }
}
