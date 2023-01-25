<?php

namespace App\Repository;

use App\Entity\PrProgrammation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrProgrammation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrProgrammation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrProgrammation[]    findAll()
 * @method PrProgrammation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrProgrammationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrProgrammation::class);
    }

    // /**
    //  * @return PrProgrammation[] Returns an array of PrProgrammation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PrProgrammation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
