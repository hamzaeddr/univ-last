<?php

namespace App\Repository;

use App\Entity\ExSnotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExSnotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExSnotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExSnotes[]    findAll()
 * @method ExSnotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExSnotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExSnotes::class);
    }

    // /**
    //  * @return ExSnotes[] Returns an array of ExSnotes objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExSnotes
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getStatutByColumn($inscription, $semestre, $statut)
    {
        // dd('e.'.$statut);
        if($statut == "statutDef") {
            $request = $this->createQueryBuilder('e')
                ->select("statut.abreviation")
                ->innerJoin("e.statutDef", "statut")
                ->where('e.semestre = :semestre')
                ->andWhere('e.inscription = :inscription')
                ->setParameter('semestre', $semestre)
                ->setParameter('inscription', $inscription)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        } else {
            $request = $this->createQueryBuilder('e')
                ->select("statut.abreviation")
                ->innerJoin("e.statutAff", "statut")
                ->where('e.semestre = :semestre')
                ->andWhere('e.inscription = :inscription')
                ->setParameter('semestre', $semestre)
                ->setParameter('inscription', $inscription)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        }
        if(!$request) {
            return "";
        } 

        return $request;
    }
    public function getStatutAffDef($inscription, $semestre, $statut)
    {
        $request = $this->createQueryBuilder('e')
            ->select("e.note, statut.abreviation")
            ->leftJoin("e.".$statut, "statut")
            ->where('e.semestre = :semestre')
            ->andWhere('e.inscription = :inscription')
            ->setParameter('semestre', $semestre)
            ->setParameter('inscription', $inscription)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        // dd($request);
        return $request['note'] . "/" . $request['abreviation'];
        // return $request;
    }
    public function GetSemestreByCodeAnneeCodePromotion($annee, $promotion, $inscription, $minOrMax, $statut)
    {
        if ($minOrMax == 'min') {
            $minOrMax = 'asc';
            $limit = 1;
        } else if ($minOrMax == 'max') {
            $minOrMax = 'desc';
            $limit = 1;
        } elseif ($minOrMax == 'all') {
            $minOrMax = 'asc ';
            $limit = 100000000000;
        }
        return $this->createQueryBuilder('s')
            ->innerJoin("s.inscription", 'inscription')
            ->innerJoin("s.semestre", 'semestre')
            ->innerJoin("semestre.modules", 'modules')
            ->where("inscription = :inscription")
            ->andWhere('inscription.annee = :annee')
            ->andWhere('semestre.promotion = :promotion')
            ->andWhere('modules.type != :A')
            ->setParameter('inscription', $inscription)
            ->setParameter('annee', $annee)
            ->setParameter('promotion', $promotion)
            ->setParameter('A', "A")
            ->groupBy("s.id")
            ->orderBy("s.".$statut, $minOrMax)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
    public function GetCategorieSemestreByCodeAnnee($annee, $inscription)
    {
        return $this->createQueryBuilder('s')
            ->innerJoin("s.inscription", 'inscription')
            ->innerJoin("s.semestre", 'semestre')
            ->where("inscription = :inscription")
            ->andWhere('inscription.annee = :annee')            
            ->setParameter('inscription', $inscription)
            ->setParameter('annee', $annee)
            ->orderBy("s.semestre", "asc")
            ->getQuery()
            ->getResult()
        ;
    }
}
