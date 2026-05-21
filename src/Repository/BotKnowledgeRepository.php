<?php

namespace App\Repository;

use App\Entity\BotKnowledge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BotKnowledge>
 */
class BotKnowledgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotKnowledge::class);
    }

    /**
     * Related knowledge when there is no direct hit: any stored keyword containing a significant word from the user message.
     * Wider than resolveFromKnowledgeBase() so Gemini still gets Growfico context (e.g. "marcot timeline" → marcot* rows).
     *
     * @return BotKnowledge[]
     */
    public function findRelatedSnippetsForGemini(string $normalizedUserQuery, int $limit = 8): array
    {
        $words = array_values(array_filter(
            preg_split('/\s+/', $normalizedUserQuery) ?: [],
            static fn (string $w) => mb_strlen($w) >= 3
        ));
        $words = array_slice(array_unique($words), 0, 8);
        if ($words === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('k');
        $orX = [];
        foreach ($words as $i => $word) {
            $param = 'tok' . $i;
            $orX[] = $qb->expr()->like('k.keyword', ':' . $param);
            $qb->setParameter($param, '%' . $word . '%');
        }

        return $qb->where($qb->expr()->orX(...$orX))
            ->orderBy('LENGTH(k.keyword)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return BotKnowledge[] Returns an array of BotKnowledge objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BotKnowledge
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
