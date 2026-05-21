<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /**
     * Quiz for a course with questions and answer choices loaded (for exam UI and grading).
     */
    public function findOneForCourseWithQuestionsAndChoices(Course $course): ?Quiz
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.questions', 'qu')->addSelect('qu')
            ->leftJoin('qu.answerChoices', 'ac')->addSelect('ac')
            ->andWhere('q.course = :course')
            ->setParameter('course', $course)
            ->orderBy('qu.id', 'ASC')
            ->addOrderBy('ac.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Quiz[] Returns an array of Quiz objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Quiz
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
