<?php

namespace App\Repository;

use App\Entity\ExEnotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExEnotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExEnotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExEnotes[]    findAll()
 * @method ExEnotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExEnotesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExEnotes::class);
    }

    // /**
    //  * @return ExEnotes[] Returns an array of ExEnotes objects
    //  */
    
    

    /*
    

    SELECT ex_e.*,ex_m.note as 'note_module' FROM  ex_mnotes ex_m 

inner join ac_element ele on ele.code_module= ex_m.code_module
inner join ex_enotes ex_e on ex_e.code_element = ele.code and ex_m.code_inscription = ex_e.code_inscription
where ex_m.code_module = '$code_module' 
and ex_m.code_annee = '$code_annee'
and ex_e.code_inscription = '$code_inscription' group by ex_e.id order by ex_e.$statut $minOrMax 
    */
    public function GetElementsByCodeAnneeCodeModule($annee, $module, $inscription, $minOrMax, $statut)
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
        return $this->createQueryBuilder('e')
            ->innerJoin("e.element", 'element')
            ->innerJoin("element.module", 'module')
            ->innerJoin("e.inscription", 'inscription')
            ->where("inscription = :inscription")
            ->andWhere('inscription.annee = :annee')
            ->andWhere('module = :module')
            ->setParameter('inscription', $inscription)
            ->setParameter('annee', $annee)
            ->setParameter('module', $module)
            ->groupBy("e.id")
            ->orderBy("e.".$statut, $minOrMax)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
    public function findByModule($module, $inscription)
    {
        
        return $this->createQueryBuilder('e')
            ->innerJoin("e.element", 'element')
            ->innerJoin("element.module", 'module')
            ->innerJoin("e.inscription", 'inscription')
            ->where("inscription = :inscription")
            ->andWhere('module = :module')
            ->setParameter('inscription', $inscription)
            ->setParameter('module', $module)
            ->getQuery()
            ->getResult()
        ;
    }
}
