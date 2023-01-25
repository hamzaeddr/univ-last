<?php

namespace App\Repository;

use App\Entity\PlEmptime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PlEmptime|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlEmptime|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlEmptime[]    findAll()
 * @method PlEmptime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlEmptimeRepository extends ServiceEntityRepository
{
    private $em;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlEmptime::class);
        $this->em = $registry->getManager();
    }

    // /**
    //  * @return PlEmptime[] Returns an array of PlEmptime objects
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
    public function findOneBySomeField($value): ?PlEmptime
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    
    public function getEmptimeBySemestreAndGroupe($semestre,$groupes)
    {
        return $this->createQueryBuilder('e')
            ->leftJoin("e.groupe", "groupe")
            ->innerJoin("e.programmation", "programmation")
            ->innerJoin("programmation.element", "element")
            ->innerJoin("element.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->where('semestre.id = :semestre')
            ->andWhere("e.active = 1")
            ->andWhere("e.annuler = 0")
            ->andWhere('groupe in (:groupes) or e.groupe is null')
            ->setParameter('semestre', $semestre)
            ->setParameter('groupes', $groupes)
            ->getQuery()
            ->getResult()
        ;
    }
    // public function getEmptimeBySemestreAndGroupe($semestre,$groupe)
    // {
    //     return $this->createQueryBuilder('e')
    //         ->innerJoin("e.groupe", "groupe")
    //         ->innerJoin("e.programmation", "programmation")
    //         ->innerJoin("programmation.element", "element")
    //         ->innerJoin("element.module", "module")
    //         ->innerJoin("module.semestre", "semestre")
    //         ->where('semestre.id = :semestre')
    //         ->andWhere("groupe = :groupe")
    //         ->andWhere("e.active = 1")
    //         ->setParameter('semestre', $semestre)
    //         ->setParameter('groupe', $groupe)
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }
    public function getEmptimeBySemestre($semestre)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin("e.programmation", "programmation")
            ->innerJoin("programmation.element", "element")
            ->innerJoin("element.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->where('semestre.id = :semestre')
            ->andWhere("e.active = 1")
            ->andWhere("e.annuler = 0")
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getEmptimeBySemestreAndGroupeAndSemaine($semestre,$groupe,$semaine)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin("e.groupe", "groupe")
            ->innerJoin("e.semaine", "semaine")
            ->innerJoin("e.programmation", "programmation")
            ->innerJoin("programmation.element", "element")
            ->innerJoin("element.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->where('semestre.id = :semestre')
            ->andWhere("groupe = :groupe")
            ->andWhere("semaine = :semaine")
            ->andWhere("e.active = 1")
            // ->andWhere("e.annuler = 0")
            ->setParameter('semestre', $semestre)
            ->setParameter('groupe', $groupe)
            ->setParameter('semaine', $semaine)
            ->getQuery()
            ->getResult()
        ;
    }
    public function getEmptimeBySemestreAndSemaine($semestre,$semaine)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin("e.semaine", "semaine")
            ->innerJoin("e.programmation", "programmation")
            ->innerJoin("programmation.element", "element")
            ->innerJoin("element.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->where('semestre.id = :semestre')
            ->andWhere("semaine = :semaine")
            ->andWhere("e.active = 1")
            ->setParameter('semestre', $semestre)
            ->setParameter('semaine', $semaine)
            ->getQuery()
            ->getResult()
        ;
    }
    public function getEmptimeBySemestreAndGroupeAndSemaineToGenerer($semestre,$groupe,$semaine)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin("e.groupe", "groupe")
            ->innerJoin("e.semaine", "semaine")
            ->innerJoin("e.programmation", "programmation")
            ->innerJoin("programmation.element", "element")
            ->innerJoin("element.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->where('semestre.id = :semestre')
            ->andWhere("groupe = :groupe")
            ->andWhere("semaine = :semaine")
            ->andWhere("e.active = 1")
            ->andWhere("e.valider = 1")
            ->setParameter('semestre', $semestre)
            ->setParameter('groupe', $groupe)
            ->setParameter('semaine', $semaine)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function getEmptimeBySemestreAndSemaineToGenerer($semestre,$semaine)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin("e.programmation", "programmation")
            ->innerJoin("programmation.element", "element")
            ->innerJoin("element.module", "module")
            ->innerJoin("module.semestre", "semestre")
            ->innerJoin("e.semaine", "semaine")
            ->where('semestre.id = :semestre')
            ->andWhere("semaine.id = :semaine")
            ->andWhere("e.active = 1")
            ->andWhere("e.valider = 1")
            ->setParameter('semestre', $semestre)
            ->setParameter('semaine', $semaine)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function getNbr_sc_regroupe($seance)
    {
        $sqls="select count(*) as nbr_sc_regroupe 
        from pr_programmation prog 
        where prog.regroupe = (select prog.regroupe from pl_emptime emp inner join pr_programmation prog on emp.programmation_id = prog.id where emp.id = '$seance' group by prog.id)";
        $stmts = $this->em->getConnection()->prepare($sqls);
        $resultSets = $stmts->executeQuery();
        $nbr_sc_regroupe = $resultSets->fetch();
        return $nbr_sc_regroupe['nbr_sc_regroupe'];
    }

    public function GetEnsMontByIdSceance($seance)
    {
        $sqls="SELECT pl.id as seance ,frm.id AS formation,ens.id AS enseignant,prog.nature_epreuve_id , TIMESTAMPDIFF(MINUTE, pl.heur_db , pl.heur_fin)/60 AS nbr_heure,gr.montant, (TIMESTAMPDIFF(MINUTE, pl.heur_db , pl.heur_fin)/60) * gr.montant AS Mt_tot2 , 
        (select count(*) from pr_programmation prog 
        where prog.regroupe = (select prog.regroupe from pl_emptime emp 
        inner join pr_programmation prog on emp.programmation_id = prog.id 
        where emp.id = $seance group by prog.id)) 
        as nbr_sc_regroupe , CASE WHEN prog.regroupe IS NOT NULL AND prog.categorie = 'S' THEN 0
        ELSE (TIMESTAMPDIFF(MINUTE, pl.heur_db , pl.heur_fin)/60) * gr.montant END AS Mt_tot
        FROM pl_emptime pl
        INNER JOIN pr_programmation prog on prog.id = pl.programmation_id 
        INNER JOIN ac_element ele on ele.id = prog.element_id
        INNER JOIN ac_module mdl on mdl.id = ele.module_id
        INNER JOIN ac_semestre sem on sem.id = mdl.semestre_id
        INNER JOIN ac_promotion prom on prom.id = sem.promotion_id
        INNER JOIN ac_formation frm on frm.id = prom.formation_id
        INNER JOIN ac_etablissement etab on etab.id = frm.etablissement_id
        INNER join pl_emptimens pl_ens on pl.id = pl_ens.seance_id  and pl_ens.active = 1
        INNER JOIN penseignant ens ON ens.id = pl_ens.enseignant_id
        INNER JOIN type_element tele ON tele.id = ele.nature_id
        left join pensgrille gr on gr.grade_id = ens.grade_id AND gr.formation_id = frm.id aND prog.nature_epreuve_id  = gr.type_epreuve_id 
        AND gr.nature = tele.code 
        -- AND ele.nature = gr.nature
        WHERE pl.id = $seance";
        // dd($sqls);
        $stmts = $this->em->getConnection()->prepare($sqls);
        $resultSets = $stmts->executeQuery();
        $result = $resultSets->fetchAll();
        return $result;
    }
    
    
    
}
