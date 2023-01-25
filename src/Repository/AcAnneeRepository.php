<?php

namespace App\Repository;

use App\Entity\AcAnnee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AcAnnee|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcAnnee|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcAnnee[]    findAll()
 * @method AcAnnee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcAnneeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcAnnee::class);
    }

    // /**
    //  * @return AcAnnee[] Returns an array of AcAnnee objects
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

    
    public function getActiveAnneeByFormation($formation): ?AcAnnee
    {
        return $this->createQueryBuilder('a')
            ->where('a.validation_academique = :non')
            ->andWhere('a.cloture_academique = :non')
            ->andWhere('a.formation = :formation')
            ->setParameter('formation', $formation)
            ->setParameter('non', "non")
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    
}
