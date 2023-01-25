<?php

namespace App\Repository;

use App\Entity\TInscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TInscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method TInscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method TInscription[]    findAll()
 * @method TInscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TInscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TInscription::class);
    }

    // /**
    //  * @return TInscription[] Returns an array of TInscription objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TInscription
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getActiveInscriptionByCurrentAnnee($annee)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin("t.annee", "annee")
            ->innerJoin("t.admission", "admission")
            ->innerJoin("admission.preinscription", "preinscription")
            ->innerJoin("preinscription.etudiant", "etudiant")
            // ->Where("annee.validation_academique = 'non'")
            // ->andWhere("annee.cloture_academique = 'non'")
            ->Where("t.statut in (13,14)")
            // ->andWhere('preinscription.active = 1')
            ->andWhere('preinscription.inscriptionValide = 1')
            ->andWhere('annee.designation = :annee')
            ->setParameter('annee', $annee)
            // ->andWhere("admission.statut = 7")
            // ->andWhere("preinscription.statut = 17")
            ->getQuery()
            ->getResult()
        ;
        // dd($return);
    }
    public function getActiveInscriptionByAnnee($admission, $annee)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin("t.annee", "annee")
            ->innerJoin("t.statut", "statut")
            ->where('t.admission = :admission')
            ->andWhere("annee.designation = :annee")
            ->andWhere("statut.id = 13")
            ->setParameter('admission', $admission)
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    public function getInscriptionsByEpreuve($epreuve)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin("t.promotion", "promotion")
            ->innerJoin("t.annee", "annee")
            ->innerJoin("t.statut", "statut")
            ->where('promotion = :promotion')
            ->andWhere("annee = :annee")
            ->andWhere("statut.id = 13")
            ->setParameter('promotion', $epreuve->getElement()->getModule()->getSemestre()->getPromotion())
            ->setParameter('annee', $epreuve->getAnnee())
            ->getQuery()
            ->getResult()
        ;
    }
    public function getInscriptionsByAnneeAndPromo($annee, $promotion, $order)
    {
        if($order == 2) {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }
        $request =  $this->createQueryBuilder('t')
            ->innerJoin("t.statut", "statut")
            ->innerJoin("t.admission", "admission")
            ->innerJoin("admission.preinscription", "preinscription")
            ->innerJoin("preinscription.etudiant", "etudiant")
            ->where('t.promotion = :promotion')
            ->andWhere("t.annee = :annee")
            ->andWhere("statut.id = 13")
            ->setParameter('promotion', $promotion)
            ->setParameter('annee', $annee)
            ->orderBy('etudiant.nom', $order)
            ->getQuery()
            ->getResult()
        ;
        return $request;
    }
    public function getInscriptionsByAnneeAndPromoAndElement($annee, $promotion,$element, $order)
    {
        if($order == 2) {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }
        $request =  $this->createQueryBuilder('t')
            ->innerJoin("t.statut", "statut")
            ->innerJoin("t.admission", "admission")
            ->innerJoin("admission.preinscription", "preinscription")
            ->innerJoin("preinscription.etudiant", "etudiant")
            ->innerJoin("t.enotes", "enotes")
            ->innerJoin("enotes.element", "element")
            ->where('t.promotion = :promotion')
            ->andWhere('element = :element')
            ->andWhere("t.annee = :annee")
            ->andWhere("statut.id = 13")
            ->setParameter('promotion', $promotion)
            ->setParameter('element', $element->getId())
            ->setParameter('annee', $annee)
            ->orderBy('etudiant.nom', $order)
            ->getQuery()
            ->getResult()
        ;
        return $request;
    }
    
    public function getInscriptionsByPreinsAndAnnee($preinscription)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin("t.admission", "admission")
            ->innerJoin("admission.preinscription", "preinscription")
            ->innerJoin("preinscription.annee", "annee")
            ->where('preinscription = :preinscription')
            ->andWhere("annee = :annee")
            ->andWhere("t.code IS NOT NULL")
            ->setParameter('preinscription', $preinscription)
            ->setParameter('annee', $preinscription->getAnnee())
            ->getQuery()
            ->getResult()[0]
        ;
    }
    
    public function getCreance()
    {
        $org = ['PYT',null];
        return $this->createQueryBuilder('inscription')
            ->select("annee.designation as anndes, preinscription.code as code_pre , etudiant.nom as nom, etudiant.prenom as prenom,etablissement.abreviation as etab ,formation.designation as forma ,promotion.ordre as niveau,inscription.typeCand as type , organisme.abreviation as org,  SUM(operationdet.montant) as montant_det , sum(distinct reglement.montant) as montant_reg , ( SUM(operationdet.montant)  - sum(distinct reglement.montant)) as creance
            ")
            ->Join("inscription.admission", "admission")
            ->Join("inscription.promotion", "promotion")
            ->Join("admission.preinscription", "preinscription")
            ->Join("preinscription.annee", "annee")
            ->Join("annee.formation", "formation")
            ->Join("formation.etablissement", "etablissement")
            ->Join("preinscription.operationcabs", "operationcab")
            ->Join("operationcab.operationdets", "operationdet")
            ->Join("operationcab.reglements", "reglement")
            ->leftJoin("operationcab.organisme", "organisme")
            ->Join("preinscription.etudiant", "etudiant")
            ->Join("etudiant.natureDemande", "naturedemande")
            ->Where("annee.cloture_academique='non'")
            ->andWhere('organisme.abreviation IN (:ids)')
            ->setParameter('ids', $org)
            ->groupBy('preinscription.code , annee.id')
            ->orderBy('etablissement.abreviation', 'ASC')
            ->getQuery()
            // ->getResult()
        ;
        // dd($return);
    }

    public function getNiveaux($promotion,$annee)
    {
        return $this->createQueryBuilder('inscription')
            ->Where("inscription.promotion = :promotion")
            ->AndWhere("inscription.annee = :annee")
            ->AndWhere("inscription.groupe is not null")
            ->setParameter('promotion', $promotion)
            ->setParameter('annee', $annee)
            ->groupBy('inscription.groupe')
            ->orderBy('inscription.groupe', 'ASC')
            ->getQuery()
            ->getResult()
        ;

    }
    public function getSalle($promotion,$annee)
    {
        return $this->createQueryBuilder('inscription')
            ->select("inscription.salle")
            ->distinct()
            ->Where("inscription.promotion = :promotion")
            ->AndWhere("inscription.annee = :annee")
            ->AndWhere("inscription.salle is not null")
            ->setParameter('promotion', $promotion)
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function getInscriptionsByAnneeAndPromoAndSalle($salle, $annee, $promotion, $order)
    {
        // dd($order);
        if($order == 1) {
            $type = "etudiant.nom";
            $order = 'ASC';
        } else if($order == 2) {
            $type = "etudiant.nom";
            $order = 'DESC';
        
        } else if($order == 3) {
            $type = "etudiant.prenom";
            $order = 'ASC';
        }
        else if($order == 4) {
            $type = "etudiant.prenom";
            $order = 'DESC';
        }
        else if($order == 5) {
            $type = "t.id";
            $order = 'ASC';
        }
        else  {
            $type = "t.id";
            $order = 'DESC';
        }
        $request =  $this->createQueryBuilder('t')
            ->innerJoin("t.statut", "statut")
            ->innerJoin("t.admission", "admission")
            ->innerJoin("admission.preinscription", "preinscription")
            ->innerJoin("preinscription.etudiant", "etudiant")
            ->where('t.promotion = :promotion')
            ->andWhere("t.annee = :annee")
            ->andWhere("statut.id = 13")
            ->andWhere("t.salle = :salle")
            ->setParameter('promotion', $promotion)
            ->setParameter('annee', $annee)
            ->setParameter('salle', $salle)
            ->orderBy($type, $order)
            ->getQuery()
            ->getResult()
        ;
        return $request;
    }

    
    public function getInscriptionsByAnneeAndPromoAndGroupe($promotion,$annee,$groupe)
    {
        // if ($groupe == Null) {
        //     # code...
        // }
        return $this->createQueryBuilder('inscription')
        ->innerJoin("inscription.annee", "annee")
        ->innerJoin("inscription.promotion", "promotion")
        ->leftJoin("inscription.groupe", "groupe")
        ->where('promotion = :promotion')
        ->andWhere("annee = :annee")
        ->andWhere("groupe = :groupe")
        ->setParameter('promotion', $promotion)
        ->setParameter('groupe', $groupe)
        ->setParameter('annee', $annee)
        ->getQuery()
        ->getResult()
        ;
    } 

    
    public function getInscriptionsByAnneeAndPromoNoGroup($promotion,$annee)
    {
        // if ($groupe == Null) {
        //     # code...
        // }
        return $this->createQueryBuilder('inscription')
        ->innerJoin("inscription.annee", "annee")
        ->innerJoin("inscription.promotion", "promotion")
        ->where('promotion = :promotion')
        ->andWhere("annee = :annee")
        ->setParameter('promotion', $promotion)
        ->setParameter('annee', $annee)
        ->getQuery()
        ->getResult()
        ;
    }

    public function getPreviousInsription($inscription)
    {
        $previousInscription =  $this->createQueryBuilder('i')
        ->innerJoin("i.statut", "statut")
        ->where('i.admission = :admission')
        ->andWhere("i.id < :id")
        ->andWhere("statut.id = 13")
        ->setParameter('admission', $inscription->getAdmission())
        ->setParameter('id', $inscription->getId())
        ->setMaxResults(1)
        ->orderBy("i.id", "desc")
        ->getQuery()
        ->getOneOrNullResult()
        ;

        if($previousInscription && ($inscription->getPromotion()->getId() === $previousInscription->getPromotion()->getId() )) {
            $previousPreviousInscription =  $this->createQueryBuilder('i')
                ->innerJoin("i.statut", "statut")
                ->where('i.admission = :admission')
                ->andWhere("i.id < :id")
                ->andWhere("statut.id = 13")
                ->setParameter('admission', $inscription->getAdmission())
                ->setParameter('id', $inscription->getId())
                ->setMaxResults(1)
                ->orderBy("i.id", "desc")
                ->getQuery()
                ->getOneOrNullResult()
            ;
            if($previousPreviousInscription && ($previousPreviousInscription->getPromotion()->getId() === $previousInscription->getPromotion()->getId() )) {
                return null;
            } 
            
            return $previousInscription;
            
        }
        return null;
    } 
    
}
