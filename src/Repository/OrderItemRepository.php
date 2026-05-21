<?php

namespace App\Repository;

use App\Entity\OrderItem;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * Returns top selling products with their total quantity sold.
     * Each element: ['product' => Product, 'totalSold' => int]
     *
     * @return array<array{product: Product, totalSold: int}>
     */
    public function findTopSellingProducts(int $limit = 10): array
    {
        $results = $this->createQueryBuilder('oi')
            ->select('p.id as productId', 'SUM(oi.quantity) as totalSold')
            ->join('oi.product', 'p')
            ->join('oi.order', 'o')
            ->where('o.status = :status')
            ->setParameter('status', 'completed')
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($results)) {
            return [];
        }

        $productIds = array_map(fn ($r) => (int) $r['productId'], $results);
        $products = $this->getEntityManager()->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getResult();

        $productMap = [];
        foreach ($products as $p) {
            $productMap[$p->getId()] = $p;
        }

        $out = [];
        foreach ($results as $row) {
            $id = (int) $row['productId'];
            if (isset($productMap[$id])) {
                $out[] = [
                    'product' => $productMap[$id],
                    'totalSold' => (int) $row['totalSold'],
                ];
            }
        }
        return $out;
    }
}
