<?php

namespace App\Repository;

use App\Entity\AcEtatablissement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AcEtatablissement>
 *
 * @method AcEtatablissement|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcEtatablissement|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcEtatablissement[]    findAll()
 * @method AcEtatablissement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcEtatablissementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcEtatablissement::class);
    }

    public function add(AcEtatablissement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AcEtatablissement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return AcEtatablissement[] Returns an array of AcEtatablissement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AcEtatablissement
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
