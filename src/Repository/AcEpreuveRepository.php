<?php

namespace App\Repository;

use App\Entity\AcEpreuve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AcEpreuve|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcEpreuve|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcEpreuve[]    findAll()
 * @method AcEpreuve[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcEpreuveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcEpreuve::class);
    }

    // /**
    //  * @return AcEpreuve[] Returns an array of AcEpreuve objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AcEpreuve
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
