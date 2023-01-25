<?php

namespace App\Repository;

use App\Entity\AcElement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AcElement|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcElement|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcElement[]    findAll()
 * @method AcElement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcElementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcElement::class);
    }

    // /**
    //  * @return AcElement[] Returns an array of AcElement objects
    //  */
    
    public function getElementsBySemestre($semestre)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin("a.module", "module")
            ->where('module.semestre = :semestre')
            ->andWhere("module.active = 1")
            ->andWhere("a.active = 1")
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getResult()
        ;
    }
    public function getElementsByPromotion($promotion)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin("a.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->where('semestre.promotion = :promotion')
            ->andWhere("module.active = 1")
            ->andWhere("a.active = 1")
            ->setParameter('promotion', $promotion)
            ->getQuery()
            ->getResult()
        ;
    }
    

    /*
    public function findOneBySomeField($value): ?AcElement
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
