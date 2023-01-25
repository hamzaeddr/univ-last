<?php

namespace App\Repository;

use App\Entity\Xseance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Xseance>
 *
 * @method Xseance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Xseance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Xseance[]    findAll()
 * @method Xseance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XseanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Xseance::class);
    }

    public function add(Xseance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Xseance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

   /**
    * @return Xseance[] Returns an array of Xseance objects
    */
   public function findseance($value): array
   {
       return $this->createQueryBuilder('x')
           ->andWhere('x.id = :val')
           ->setParameter('val', $value)
           ->orderBy('x.id', 'ASC')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult()
       ;
   }


}
