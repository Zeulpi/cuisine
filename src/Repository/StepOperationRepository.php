<?php

namespace App\Repository;

use App\Entity\StepOperation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepOperation>
 */
class StepOperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepOperation::class);
    }

//    /**
//     * @return StepOperation[] Returns an array of StepOperation objects
//     */
   public function findByStepId($value): array
   {
       return $this->createQueryBuilder('ope')
           ->andWhere('ope.step = :stepId')
           ->setParameter('stepId', $value)
           ->orderBy('ope.id', 'ASC')
           ->getQuery()
           ->getResult()
       ;
   }

//    public function findOneBySomeField($value): ?StepOperation
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
