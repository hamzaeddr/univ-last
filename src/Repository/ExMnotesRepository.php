<?php

namespace App\Repository;

use App\Entity\ExMnotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExMnotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExMnotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExMnotes[]    findAll()
 * @method ExMnotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExMnotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExMnotes::class);
    }

    // /**
    //  * @return ExMnotes[] Returns an array of ExMnotes objects
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

    
    public function getStatutByColumn($inscription, $module, $statut)
    {
        // dd('e.'.$statut);
        if($statut == "statutDef") {
            $request = $this->createQueryBuilder('e')
                ->select("statut.abreviation")
                ->innerJoin("e.statutDef", "statut")
                ->where('e.module = :module')
                ->andWhere('e.inscription = :inscription')
                ->setParameter('module', $module)
                ->setParameter('inscription', $inscription)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        } else {
            $request = $this->createQueryBuilder('e')
                ->select("statut.abreviation")
                ->innerJoin("e.statutAff", "statut")
                ->where('e.module = :module')
                ->andWhere('e.inscription = :inscription')
                ->setParameter('module', $module)
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
    public function getStatutAffDef($inscription, $module)
    {
        $request = $this->createQueryBuilder('e')
            ->select("statutAff.abreviation as abreviationAff,statutDef.abreviation as abreviationDef ")
            ->innerJoin("e.statutAff", "statutAff")
            ->innerJoin("e.statutDef", "statutDef")
            ->where('e.module = :module')
            ->andWhere('e.inscription = :inscription')
            ->setParameter('module', $module)
            ->setParameter('inscription', $inscription)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        if(!$request) {
            return [
                'abreviationAff' => "",
                'abreviationDef' => ""
            ];
        } 

        return $request;
    }
    

    public function GetModuleByCodeAnneeCodeSemstre($annee, $semestre, $inscription, $minOrMax, $statut)
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
        return $this->createQueryBuilder('m')
            ->innerJoin("m.inscription", 'inscription')
            ->innerJoin("m.module", 'module')
            ->where("inscription = :inscription")
            ->andWhere('inscription.annee = :annee')
            ->andWhere('module.semestre = :semestre')
            ->andWhere('module.type <> :letter')
            ->setParameter('inscription', $inscription)
            ->setParameter('annee', $annee)
            ->setParameter('semestre', $semestre)
            ->setParameter('letter', 'A')
            ->groupBy("m.id")
            ->orderBy("m.".$statut, $minOrMax)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
    public function GetNbrModuleByInscription($annee, $inscription, $note) {
        return $this->createQueryBuilder('m')
            ->select("count(m) as nbr_modules")
            ->innerJoin("m.inscription", 'inscription')
            ->innerJoin("m.module", 'module')
            ->where("inscription = :inscription")
            ->andWhere('inscription.annee = :annee')
            ->andWhere('module.type != :A')
            ->andWhere('m.note < :note')
            ->setParameter('inscription', $inscription)
            ->setParameter('annee', $annee)
            ->setParameter('note', $note)
            ->setParameter('A', "A")
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function getNotesModuleBySemestre($semestre, $inscription) {
        return $this->createQueryBuilder('m')
            ->innerJoin("m.inscription", 'inscription')
            ->innerJoin("m.module", 'module')
            ->where("inscription = :inscription")
            ->andWhere('module.semestre = :semestre')
            ->setParameter('inscription', $inscription)
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getResult()
        ;
    }
    public function getNotesModuleSansAssiduiteBySemestre($semestre, $inscription) {
        
        return $this->createQueryBuilder('m')
            ->innerJoin("m.inscription", 'inscription')
            ->innerJoin("m.module", 'module')
            ->where("inscription = :inscription")
            ->andWhere('module.semestre = :semestre')
            ->andWhere('module.type != :lettre')
            ->setParameter('inscription', $inscription)
            ->setParameter('semestre', $semestre)
            ->setParameter('lettre', "A")
            ->getQuery()
            ->getResult()
        ;
    }
    
}
