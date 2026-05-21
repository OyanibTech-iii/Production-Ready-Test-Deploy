<?php

namespace App\Repository;

use App\Entity\Stock;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * Finds an existing stock record with the exact same products, type, and location.
     */
    public function findExistingStock(array $products, ?string $type, ?Location $location): ?Stock
    {
        // 1. Find all stocks that match the type and location
        $qb = $this->createQueryBuilder('s')
            ->join('s.products', 'p') // Join to products to filter earlier
            ->where('s.stockType = :type')
            ->andWhere('p.id IN (:productIds)')
            ->setParameter('type', $type)
            ->setParameter('productIds', array_map(fn($p) => $p->getId(), $products));

        if ($location) {
            $qb->andWhere('s.location = :location')
                ->setParameter('location', $location);
        } else {
            $qb->andWhere('s.location IS NULL');
        }

        $candidates = $qb->getQuery()->getResult();

        // 2. Filter candidates by matching the exact product collection
        foreach ($candidates as $stock) {
            $existingProducts = $stock->getProducts()->toArray();

            if (count($existingProducts) === count($products)) {
                // Get IDs to compare accurately
                $existingIds = array_map(fn($p) => $p->getId(), $existingProducts);
                $newIds = array_map(fn($p) => $p->getId(), $products);

                sort($existingIds);
                sort($newIds);

                if ($existingIds === $newIds) {
                    return $stock;
                }
            }
        }

        return null;
    }

    public function findLatestForProduct(int $productId): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->join('s.products', 'p')
            ->andWhere('p.id = :pid')
            ->setParameter('pid', $productId)
            ->orderBy('s.updatedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}