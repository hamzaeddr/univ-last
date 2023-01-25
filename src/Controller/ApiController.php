<?php

namespace App\Controller;

use App\Entity\PFrais;
use App\Entity\AcAnnee;
use App\Entity\PGroupe;
use App\Entity\XBanque;
use App\Entity\Xseance;
use App\Entity\AcModule;
use App\Entity\ExGnotes;
use App\Entity\UsModule;
use App\Entity\AcElement;
use App\Entity\AcEpreuve;
use App\Entity\TEtudiant;
use App\Entity\AcSemestre;
use App\Entity\POrganisme;
use App\Entity\TAdmission;
use App\Entity\AcFormation;
use App\Entity\AcPromotion;
use App\Entity\UsOperation;
use App\Entity\TInscription;
use App\Entity\UsSousModule;
use App\Entity\NatureDemande;
use App\Entity\TOperationcab;
use App\Entity\PNatureEpreuve;
use App\Entity\AcEtablissement;
use App\Entity\Checkinout;
use App\Entity\PlEmptime;
use App\Entity\PrProgrammation;
use App\Entity\PSalles;
use App\Entity\Userinfo;
use App\Entity\XseanceAbsences;
use App\Entity\XseanceCapitaliser;
use App\Entity\XseanceMotifAbs;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as xlsxx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require '../zklib/zklib/ZKLib.php';
// require __DIR__ . '../../../vendor/autoload.php';

#[Route('/api')]
class ApiController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        // $this->emUniv = $doctrine->getManager("univ");
        // $em = $this->getDoctrine()->getManager();
    }
    #[Route('/etablissement', name: 'getetablissement')]
    public function getetbalissement(): Response
    {
        $etbalissements = $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $data = self::dropdown($etbalissements,'Etablissement');
        return new JsonResponse($data);
    }
    #[Route('/formation/{id}', name: 'getformation')]
    public function getformation($id): Response
    {
        $formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>$id]);
        $data = self::dropdown($formations,'Formation');
        return new JsonResponse($data);
    }

    
    #[Route('/promotion/{formation}', name: 'getPromotion')]
    public function getPromotion(AcFormation $formation): Response
    {   
        $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>$formation, 'active' => 1],['id'=>'ASC']);
        $data = self::dropdown($promotions,'promotion');
        return new JsonResponse($data);
    }
    #[Route('/annee/{id}', name: 'getAnnee')]
    public function getAnnee($id): Response
    {   
        $annee = $this->em->getRepository(AcAnnee::class)->findBy(['formation'=>$id, 'active' => 1],['designation'=>'DESC']);
        $data = self::dropdown($annee,'Annee');
        return new JsonResponse($data);
    }
    
    #[Route('/semestre/{id}', name: 'getSemestre')]
    public function getSemestre($id): Response
    {   
        $semestre = $this->em->getRepository(AcSemestre::class)->findBy(['promotion'=>$id, 'active' => 1],['designation'=>'ASC']);
        $data = self::dropdown($semestre,'Semestre');
        return new JsonResponse($data);
    }

    #[Route('/module/{id}', name: 'getModule')]
    public function getModule($id): Response
    {   
        $module = $this->em->getRepository(AcModule::class)->findBy(['semestre'=>$id, 'active' => 1],['designation'=>'ASC']);
        $data = self::dropdown($module,'Module');
        return new JsonResponse($data);
    }

    #[Route('/element/{id}', name: 'getElement')]
    public function getElement($id): Response
    {   
        $element = $this->em->getRepository(AcElement::class)->findBy(['module'=>$id, 'active' => 1],['designation'=>'ASC']);
        $data = self::dropdown($element,'Element');
        return new JsonResponse($data);
    }

    #[Route('/enseignantsByProgramme/{element}/{nature_epreuve}', name: 'enseignantsByProgramme')]
    public function enseignantsByProgramme(AcElement $element,PNatureEpreuve $nature_epreuve): Response
    {   
        $programmation = $this->em->getRepository(PrProgrammation::class)->findOneBy([
            'element'=> $element,
            'nature_epreuve' => $nature_epreuve]
        );
        
        $data = "<option enabled value='' disabled='disabled'>Choix Enseignants</option>";
        if ($programmation != NULL) {
            foreach ($programmation->getEnseignants() as $enseignant) {
                $data .="<option value=".$enseignant->getId().">".$enseignant->getNom()." ".$enseignant->getPrenom()."</option>";
            }
        }
        return new JsonResponse($data);
    }
    
    #[Route('/nature_demande', name: 'nature_demande')]
    public function getnature_demande(): Response
    {
        $nature = $this->em->getRepository(NatureDemande::class)->findBy(['active' => 1]);
        $data = self::dropdown($nature,'Nature De Demande');
        return new JsonResponse($data);
    }

    #[Route('/anneeProgrammation/{formation}', name: 'anneeProgrammation')]
    public function anneeProgrammation(AcFormation $formation): Response
    {   
        $annee = $this->em->getRepository(AcAnnee::class)->findBy(['formation'=>$formation],['id'=>'DESC'],2);
        $data = self::dropdown($annee,'Annee');
        return new JsonResponse($data);
    }
    
    #[Route('/anneeresidanat/{id}', name: 'anneeResidanat')]
    public function anneeResidanat(AcFormation $formation): Response
    {   
        if((strpos($formation->getDesignation(), 'Résidanat') === false) && $formation->getEtablissement()->getId() != 25){
            return new JsonResponse(1);
        }else{
            $annee = $this->em->getRepository(AcAnnee::class)->findBy(['formation'=>$formation],['id'=>'DESC'],2);
            $data = self::dropdown($annee,'Annee');
            return new JsonResponse($data);
        }
        
    }
    #[Route('/organisme', name: 'getorganisme')]
    public function getOrganisme(): Response
    {   
        $organisme = $this->em->getRepository(POrganisme::class)->findBy(['active'=>1]);
        $data = self::dropdown($organisme,'organisme');
        return new JsonResponse($data);        
    }
    
    #[Route('/getorganismepasPayant', name: 'getorganismepasPayant')]
    public function getOrganismepasPayant(): Response
    {  
        //  dd('test');
        $organisme = $this->em->getRepository(POrganisme::class)->getorganismepasPayant();
        // dd($organisme);
        $data = self::dropdown($organisme,'organisme');
        return new JsonResponse($data);        
    }

    #[Route('/organisme/{operationcab}', name: 'getOrganismeByoperation')]
    public function getOrganismeByoperation(TOperationcab $operationcab): Response
    {   
        $organismes = $this->em->getRepository(POrganisme::class)->findBy(['active'=>1]);
        $data = "<option selected disabled value=''>Choix Organisme</option>";
        foreach ($organismes as $organisme) {
            if ($organisme === $operationcab->getOrganisme()) {
                $data .="<option selected value=".$organisme->getId().">".$organisme->getDesignation()."</option>";
            }else{
                $data .="<option value=".$organisme->getId().">".$organisme->getDesignation()."</option>";
            }
        }
        return new JsonResponse($data);        
    }
    
    #[Route('/nature_etudiant/{admission}', name: 'getnatureetudiant')]
    public function getNatureEtudiant(TAdmission $admission): Response
    {   
        $nature = $admission->getPreinscription()->getEtudiant()->getNatureDemande()->getDesignation();
        // dd($nature);
        if ($nature !== 'Payant') {
            $organisme = $this->em->getRepository(POrganisme::class)->findAll();
        }else {
            $organisme = [];
        }
        $data = self::dropdown($organisme,'organisme');
        return new JsonResponse($data);        
    }
    #[Route('/frais/{admission}', name: 'getFraisByFormation')]
    public function getFraisByFormation(TAdmission $admission): Response
    {   
        $formation = $admission->getPreinscription()->getAnnee()->getFormation();
        $operationcab = $this->em->getRepository(TOperationcab::class)->findOneBy(['preinscription'=>$admission->getPreinscription(),'categorie'=>'admission']);
        $frais = $this->em->getRepository(PFrais::class)->findBy(["formation" => $formation, 'categorie' => "admission",'active'=>1]);
        $data = self::dropdownData($frais,'frais');
                
        return new JsonResponse(['list' => $data, 'codefacture' => $operationcab->getCode()]);        
    }
  
    #[Route('/banque', name: 'getbanque')]
    public function getbanque(): Response
    {
        $banques = $this->em->getRepository(XBanque::class)->findAll();
        $data = self::dropdown($banques,'Banque');
        return new JsonResponse($data);
    }
  
    #[Route('/paiement', name: 'getpaiement')]
    public function getpaiement(): Response
    {
        $paiements = $this->em->getRepository(XModalites::class)->findAll();
        $data = self::dropdown($paiements,'Type De Paiement');
        return new JsonResponse($data);
    }
    #[Route('/nature_erpeuve/{nature}', name: 'getNatureEpreuveByNature')]
    public function getNatureEpreuveByNature($nature): Response
    {   
        $natrueEpreuves = $this->em->getRepository(PNatureEpreuve::class)->findBy(["nature" => $nature]);
        $data = self::dropdown($natrueEpreuves,'nature epreuve');
        return new JsonResponse($data);        
    }
    
    #[Route('/niv1/{promotion}', name: 'getNiv1Bypromotion')]
    public function getNiv1Bypromotion(AcPromotion $promotion): Response
    {   
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $inscriptions = $this->em->getRepository(TInscription::class)->getNiveaux($promotion,$annee);
        $data = "<option selected enabled value=''>Choix Niveau 1</option>";
        $groupes = [];
        foreach ($inscriptions as $inscription) {
            $groupe = $inscription->getGroupe();
            // if ($groupe != Null) {
                if ($groupe->getGroupe() == Null) {
                    if (!in_array($groupe, $groupes)){
                        array_push($groupes,$groupe);
                    }
                    // $data .="<option value=".$groupe->getId().">".$groupe->getNiveau()."</option>";
                }elseif ($groupe->getGroupe()->getGroupe() == Null) {
                    $groupe = $groupe->getGroupe();
                    if (!in_array($groupe, $groupes)){
                        array_push($groupes,$groupe);
                    }
                    // $data .="<option value=".$groupe->getId().">".$groupe->getNiveau()."</option>";
                }else {
                    $groupe = $groupe->getGroupe()->getGroupe();
                    if (!in_array($groupe, $groupes)){
                        array_push($groupes,$groupe);
                    }
                    // $data .="<option value=".$groupe->getId().">".$groupe->getNiveau()."</option>";
                }
                
                // $data .="<option value=".$groupe->getId().">".$groupe->getNiveau()."</option>";
                
            // }
        }
        foreach ($groupes as $groupe) {
            $data .="<option value=".$groupe->getId().">".$groupe->getNiveau()."</option>";
        }
        return new JsonResponse($data);
    }

    #[Route('/niv2/{niv1}', name: 'getNiv2ByNiv1')]
    public function getNiv2ByNiv1(PGroupe $niv1): Response
    {   
        $niveaux2 = $this->em->getRepository(PGroupe::class)->findBy(['groupe'=>$niv1]);
        $data = "<option selected enabled value=''>Choix Niveau 2</option>";
        foreach ($niveaux2 as $niveau2) {
                $data .="<option value=".$niveau2->getId().">".$niveau2->getNiveau()."</option>";
        }
        return new JsonResponse($data);     
    }

    #[Route('/niv3/{niv2}', name: 'getNiv2ByNiv3')]
    public function getNiv2ByNiv3($niv2): Response
    {   
        // $niveaux3 = $this->em->getRepository(PGroupe::class)->findBy(['groupe'=>$niv2]);
        $niveaux3 = $this->em->getRepository(PGroupe::class)->getInscriptionsByNiveaux3($niv2);
        // dd($niveaux3);
        $data = "<option selected enabled value=''>Choix Niveau 3</option>";
        foreach ($niveaux3 as $niveau3) {
                $data .="<option value=".$niveau3->getId().">".$niveau3->getNiveau()."</option>"; 
        }
        return new JsonResponse($data);       
    } 
    #[Route('/salle/{promotion}', name: 'getSalle')]
    public function getSalle(AcPromotion $promotion): Response
    {   
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        // dump($annee->getId(), $promotion->getId());
        $salles = $this->em->getRepository(TInscription::class)->getSalle($promotion, $annee);
        // dd($salles);
        $data = "<option selected enabled value=''>Choix Salle</option>";
        foreach ($salles as $salle) {
            $sallearray = explode("-", $salle['salle']);
            $data .="<option value=".$salle['salle'].">".$sallearray[0]." ". $sallearray[1] ."</option>";
         }
        return new JsonResponse($data);
    }
    
    // #[Route('/findSemaine', name: 'findSemaine')]
    // public function findSemaine(Request $request): Response
    // {
    //     dd($request->query->get("search"));
    //     $semaines = $this->em->getRepository(Semaine::class)->findSemaine($request->query->get("search"));
    //     $html = '<option value="">Choix semaine</option>';
    //     foreach ($semaines as $semaine) {
    //         // $html .= '<option value='.$semaine->getId().'>Semaine '.$semaine->getNsemaine().' de: '..' à '..{{semaine.datefin | date('j/m')}} {{semaine.datefin | date('Y')}}</option>'
    //         $html .= '<option value='.$semaine->getId().'>Semaine '.$semaine->getNsemaine().' de: '.$semaine->getDateDebut()->format('j/m').' à '.$semaine->getDateFin()->format('j/m').'</option>';
    //     }
    //     dd($html);
    //     // dd($admissions);
    //     // return new Response(json_encode($admissions));
    // }

    static function dropdown($objects,$choix)
    {
        $data = "<option selected enabled value=''>Choix ".$choix."</option>";
        foreach ($objects as $object) {
            $data .="<option value=".$object->getId().">".$object->getDesignation()."</option>";
         }
         return $data;
    }
    static function dropdownData($objects,$choix)
    {
        $data = "<option selected enabled value=''>Choix ".$choix."</option>";
        foreach ($objects as $object) {
            $data .="<option value=".$object->getId()." data-frais=".$object->getMontant().">".$object->getDesignation()."</option>";
         }
         return $data;
    }
    static function dropDownSelected($objects,$choix, $value)
    {
        $data = "<option selected enabled value=''>Choix ".$choix."</option>";
        foreach ($objects as $object) {
            if($object->getId() === $value->getId()) {
                $data .="<option value=".$object->getId()." selected>".$object->getDesignation()."</option>";
            } else {
                $data .="<option value=".$object->getId()." >".$object->getDesignation()."</option>";
            }
         }
         return $data;
    }

    static function check($user, $link, $em, $request) {
        if(!$request->getSession()->get("modules")){
            if(in_array('ROLE_ADMIN', $user->getRoles())){
                $sousModules = $em->getRepository(UsSousModule::class)->findBy([],['ordre'=>'ASC']);
            } else {
                $sousModules = $em->getRepository(UsSousModule::class)->findByUserOperations($user);
            }
            $modules = $em->getRepository(UsModule::class)->getModuleBySousModule($sousModules);
            $data = [];
            foreach($modules as $module) {
                $sousModuleArray = [];
                foreach ($sousModules as $sousModule) {
                    if($sousModule->getModule()->getId() == $module->getId()) {
                        array_push($sousModuleArray,$sousModule);
                    }
                }
                array_push($data, [
                    'module' => $module,
                    'sousModule' => $sousModuleArray
                ]);
            }
            // dd($data);
            $request->getSession()->set('modules', $data);
            
        }
        if(in_array('ROLE_ADMIN', $user->getRoles())) {
            $operations = $em->getRepository(UsOperation::class)->findAllBySousModule($link);
            return $operations;
        }
        $operations = $em->getRepository(UsOperation::class)->getOperationByLinkSousModule($user, $link);
        return $operations;
    }
    
    // /**
    //  * @Route("/gnote", name="app_gnote")
    //  */
    // public function Gnotes(): Response
    // {
    //     //1560650 last id i get
    //     $emUniv = $this->emUniv;   
    //     $gnotes ="SELECT
    //                 ex_gnotes.id as id,
    //                 note,
    //                 absent as absence,
    //                 ex_gnotes.observation,
    //                 ex_gnotes.date_creation as created ,
    //                 ex_gnotes.anonymat,
    //                 ac_epreuve.id_epreuve as epreuve_id,
    //                 t_inscription.id_inscription as inscription_id
    
    //             FROM `ex_gnotes`
    //                 INNER JOIN ac_epreuve  on ac_epreuve.code =ex_gnotes.code_epreuve
    //                 INNER JOIN t_inscription  on t_inscription.code = ex_gnotes.code_inscription
    //                 INNER JOIN ac_annee  on ac_annee.code = t_inscription.code_annee
    //                 where ac_annee.designation= '2021/2022'";

    //     $result = $emUniv->getConnection()->prepare($gnotes);
    //     $stmt = $result->executeQuery();
    //     $resulta = $stmt->fetchAll();

    //     //  dd($resulta);
    //     foreach ($resulta as $data)
    //     { 
    //         //   dd($data['created']);
    //         $epreuve = $this->em->getRepository(AcEpreuve::class)->find($data['epreuve_id']);
    //         $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription_id']);
    //         $gnote = new ExGnotes();
    //         $gnote->setEpreuve($epreuve);
    //         $gnote->setId($data['id']);
    //         $gnote->setInscription($inscription);
    //         $gnote->setUserCreated($this->getUser());
    //         $gnote->setCreated(new \DateTime($data['created']));
    //         $gnote->setAnonymat($data['anonymat']);     
    //         $gnote->setNote($data['note']);
    //         $gnote->setAbsence($data['absence']);
    //         $gnote->setObservation($data['observation']);
    //         $this->em->persist($gnote);
    //     }
    //     $this->em->flush();
  
    //     // return $this->render('gnote/index.html.twig', [
    //     //     'controller_name' => 'GnoteController',
    //     // ]);
    //     return new Response('good');
    // }



 //{#-----------------------------------------------------   Assiduite  ----------------------------------------------------------------------------#}  
 

 static function dropdownassiduite($objects,$choix)
 {
     $data = "<option selected enabled value=''>Choix ".$choix."</option>";
     foreach ($objects as $object) {
         $data .="<option value=".$object->getId().">".$object->getAbreviation()."</option>";
      }
      return $data;
 }
 static function dropdownsituation($objects,$choix)
 {
     $data = "<option selected enabled value=''>Choix ".$choix."</option>";
     foreach ($objects as $object) {
         $data .="<option value=".$object['code_admission'].">".$object['nom']." ".$object['prenom']."</option>";
      }
      return $data;
 }

 static function execute($requete,$em)
 {
    $stmt = $em->getConnection()->prepare($requete);
    $newstmt = $stmt->executeQuery();   
    $result = $newstmt->fetchAll();

    return $result;
 }

 static function traitement_P($module,$promotion,$groupe,$date)
 {
    if ($groupe == 'empty') {
        $concatenation = "";
     }
     else {
         $concatenation = "AND (x_inscription_grp.niv_1='$groupe' OR x_inscription_grp.niv_2='$groupe' OR x_inscription_grp.niv_3='$groupe')";
     }
     $requete = "SELECT x_inscription_grp.code_admission as adm,x_inscription_grp.nom,x_inscription_grp.prenom,'P' as categorie,'$date' as date
      FROM `xseance_capitaliser`

                INNER JOIN x_inscription_grp ON xseance_capitaliser.id_admission=x_inscription_grp.code_admission
                INNER JOIN ac_promotion ON ac_promotion.code=xseance_capitaliser.id_promotion
                WHERE ac_promotion.id='$promotion' AND xseance_capitaliser.id_module='$module' $concatenation"; 
    return $requete;

 }

 static function traitement_d($code_admission,$promotion,$groupe,$date)
 {
    if ($groupe == 'empty') {
       $concatenation = "";
    }
    else {
        $concatenation = "AND (x_inscription_grp.niv_1='$groupe' OR x_inscription_grp.niv_2='$groupe' OR x_inscription_grp.niv_3='$groupe')";
    }
    if (empty($code_admission)) {
        $code_admission = "''";
    }
    
    $requete="SELECT  x_inscription_grp.code_admission as adm,x_inscription_grp.nom,x_inscription_grp.prenom,'D' as categorie,'00:00' as pointage,'$date' as date
     FROM `x_inscription_grp`
     WHERE `promotion`='$promotion' AND `code_admission` not in ($code_admission) $concatenation";
    return $requete;

 }

 static function traitement_abcd($promotion,$date,$date1,$date2,$salle,$groupe)
 {
    if ($groupe == 'empty') {
        $concatenation = "";
     }
     else {
         $concatenation = "AND (x_inscription_grp.niv_1='$groupe' OR x_inscription_grp.niv_2='$groupe' OR x_inscription_grp.niv_3='$groupe')";
     }

   $requete="SELECT DISTINCT userinfo.street as adm,x_inscription_grp.nom,x_inscription_grp.prenom,x_inscription_grp.Grp_Stg,min(date_format(checkinout.CHECKTIME,'%H:%i')) as pointage,'$date' as date,
              CASE WHEN CHECKTIME>='$date1' AND CHECKTIME<=DATE_ADD('$date1', INTERVAL 30 MINUTE) THEN 'A'
                WHEN CHECKTIME>=DATE_ADD('$date1', INTERVAL 31 MINUTE) AND CHECKTIME<=DATE_ADD('$date1', INTERVAL 44 MINUTE) THEN 'B'
                WHEN CHECKTIME>=DATE_ADD('$date1', INTERVAL 45 MINUTE) AND CHECKTIME<=DATE_ADD('$date1', INTERVAL 60 MINUTE) THEN 'C'
                WHEN CHECKTIME is NULL THEN 'D'
                ELSE 'D'
                END AS categorie
              from userinfo
             inner join x_inscription_grp on userinfo.street = x_inscription_grp.code_admission
             LEFT join checkinout on checkinout.USERID = userinfo.USERID
             LEFT JOIN iseance_salle ON checkinout.sn=iseance_salle.id_pointeuse
             WHERE x_inscription_grp.promotion='$promotion'  AND checkinout.CHECKTIME LIKE '$date%' AND iseance_salle.code_salle='$salle'
             AND CHECKTIME>='$date1' AND CHECKTIME<='$date2' $concatenation GROUP BY userinfo.street ORDER BY x_inscription_grp.nom";
return $requete;
  
 }
 static function insert_pointeuse($salle,$em)
 {
    ///get_salle///////////////////////
    $query="SELECT distinct iseance_salle.code_salle,psalles.designation,machines.sn,machines.IP 
    FROM `psalles` INNER JOIN iseance_salle ON iseance_salle.code_salle=psalles.code
     INNER JOIN machines ON iseance_salle.id_pointeuse=machines.sn where psalles.designation='$salle";
     $pointeuse = self::execute($query,$em);
     foreach($pointeuse as $point){

    ///////////////////////////////////

    ///get_att///////////////////////
    $zk = new \ZKLib('172.20.10.17', 4370, 'udp');
    
    $zk->connect();
    $zk->disableDevice();
    
    
    $attendace = $zk->getAttendance('2022-11-01');
    dd($attendace);
    foreach ($attendace as $attendancedata ){
                                        $sqluser="SELECT userinfo.USERID,userinfo.USERID as exis
                                        from userinfo where Badgenumber='$attendancedata[1]' limit 1";
  
                                        $seauser = self::execute($sqluser,$em);
                                        foreach($seauser as $user){

                                            

                                        }


    }
    ///////////////////////////////////
     }
    ///get_insert///////////////////////
    ///////////////////////////////////

 }

 static function insert_xseance($idseance,$em)
 {
    // $date= date_format(date(), 'Y-m-d');

    $requete = "SELECT * FROM `v_seance` WHERE `code_seance`=$idseance";
    $seance_info = self::execute($requete,$em);
        foreach ($seance_info as $data)
        { 
          
            $seance = new Xseance();
            $seance->setIDSéance($data['code_seance']);
            $seance->setTypeséance($data['type_seance']);
            $seance->setIDEtablissement($data['etab_code']);
            $seance->setIDFormation($data['form_code']);
            $seance->setIDPromotion($data['pormo_code']);
            $seance->setIDAnnée($data['annee']);     
            $seance->setAnnéeLib($data['anneeLib']);
            $seance->setIDSemestre($data['semestre']);
            $seance->setGroupe($data['groupe']);
            $seance->setIDModule($data['module']);
            $seance->setIDElement($data['element']);
            $seance->setIDEnseignant($data['enseignant']);
            $seance->setIDSalle($data['code_salle']);
            $seance->setStatut(1);
            $seance->setDateSéance(new \DateTime($data['date_seance']));
            $seance->setSemaine($data['semaine']);
            $seance->setHeureDebut(new \DateTime($data['heure_debut']));
              $seance->setHeureFin(new \DateTime($data['heure_fin']));
            $seance->setDateSys(new \DateTime());
            $em->persist($seance);
        }
        $em->flush();
    return $seance_info;


 }
 static function insert_xseance_absc($list,$seance,$em)
 {
    foreach($list as $etud){
        $xseance_absence = new XseanceAbsences();
        $xseance_absence->setIDAdmission($etud['id_admission']);
        $xseance_absence->setIDSéance($seance);
        $xseance_absence->setNom($etud['nom']);
        $xseance_absence->setPrénom($etud['prenom']);
        $xseance_absence->setDatePointage(new \DateTime($etud['date']));
        $xseance_absence->setHeurePointage(new \DateTime($etud['pointage']));
        $xseance_absence->setCategorie($etud['categorie']);
        $em->persist($xseance_absence);


    }
    $em->flush();
    return $list;
 }

 static function retraite_seance($list,$seance,$em)
 {
    foreach($list as $etud){
        $xseance_absence = $em->getRepository(XseanceAbsences::class)->findOneBy(['ID_Admission'=>$etud['id_admission'],'ID_Séance'=>$seance]);
        $xseance_absence->setHeurePointage(new \DateTime($etud['pointage']));
        $xseance_absence->setCategorieSi($etud['categorie']);
        $em->persist($xseance_absence);
    }
    $em->flush();
    return $list;
 }

 static function remove($seance,$em)
 {
        $xseance = $em->getRepository(Xseance::class)->findOneBy(['ID_Séance'=>$seance]);
        $list = $em->getRepository(XseanceAbsences::class)->findBy(['ID_Séance'=>$seance]);


        $em->remove($xseance);
        $em->flush();

        foreach($list as $etud){

            $xseance_absence = $em->getRepository(XseanceAbsences::class)->findOneBy(['ID_Admission'=>$etud->getIDAdmission(),'ID_Séance'=>$seance]);
            $em->remove($xseance_absence);
    }
    $em->flush();
    return $seance;
 }
 static function coun_categorie($idseance)
 {
    $requete ="SELECT IF((STRCMP(categorie, 'A') = 0)or(STRCMP(categorie, 'P') = 0) or (STRCMP(categorie, 'Z') = 0),COUNT(id_admission),'0') as A,
                IF(STRCMP(categorie, 'B') = 0,COUNT(id_admission),'0') as B,IF(STRCMP(categorie, 'C') = 0,COUNT(id_admission),'0') as C,IF(STRCMP(categorie, 'D') = 0,COUNT(id_admission),'0') as D
                FROM `xseance_absences` WHERE `id_séance`=$idseance
                GROUP BY categorie";
                return $requete;

 }
 #[Route('/Etablissement_aff', name: 'Etab_aff')]
 public function Etablissemnt_aff(): Response
 {   
     $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
    
     return new JsonResponse($etbalissements);
 }

 #[Route('/Formation_aff/{etablissement}', name: 'Form_aff')]
 public function Formation_aff($etablissement): Response
 {   
     $formation = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>$etablissement,'assiduite'=>1]);

     $data = self::dropdownassiduite($formation,'Formation');
        return new JsonResponse($data);
 }
 #[Route('/Promotion_aff/{formation}', name: 'Prom_aff')]
 public function Promotio_aff($formation): Response
 {   
     $Promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>$formation]);
     $data = self::dropdown($Promotions,'Promotion');
        return new JsonResponse($data);

    
 }
 #[Route('/Seance_aff/{promotion}/{date}/{type}', name: 'seance_aff')]
 public function Seance_aff($promotion,$date,$type): Response
 {      
   if ($type == "stage") {
     $concatenation = " AND v_seance.nature_des = 'ST'";
   }
   else{
    $concatenation = "";

   }
     $requete = "SELECT  v_seance.module as idmodule,
                            v_seance.code_seance,
                            v_seance.nature_des as seance_natur,
                            v_seance.groupe,
                            v_seance.salle_des as salle,
                            v_seance.element_des as element,
                            v_seance.ens_nom ,
                            v_seance.ens_prenom,
                            TIME_FORMAT(v_seance.heure_debut, '%H:%i') AS heur_db ,
                            TIME_FORMAT(v_seance.heure_fin, '%H:%i') AS heur_fin,
                            xseance.ID_Séance as exis, xseance.Statut,
                            CASE WHEN xseance.ID_Séance is NULL THEN '0'
                            ELSE 1
                            END as Existe ,
                            xseance.Signé,
                            xseance.Annulée,v_seance.promotion,
                            v_seance.id_module,
                            v_seance.code_salle
                    FROM v_seance
                    LEFT JOIN xseance ON xseance.id_séance=v_seance.code_seance
                    WHERE  v_seance.promotion=$promotion  AND v_seance.date_seance like '$date%' $concatenation ORDER BY v_seance.heure_debut";
    $stmt = $this->em->getConnection()->prepare($requete);
    $newstmt = $stmt->executeQuery();   
    $seances = $newstmt->fetchAll();

    
    $html = $this->render('assiduite/table/datatable_seance.html.twig', [
        'seances' => $seances,
   ])->getContent();
   return new JsonResponse($html);
 }

 #[Route('/Etud_aff', name: 'Etud_aff')]
 public function Etud_aff(Request $request)
 {     
    if($request->isXmlHttpRequest()) {
        $promotion = $request->request->get('promotion');  
        $seance = $request->request->get('seance');  
        $groupe = $request->request->get('groupe');  
        $existe = $request->request->get('existe');  

            if (empty($groupe)) {
                $concatenation = "";
            }
            else {
                $concatenation = "AND (x_inscription_grp.niv_1='$groupe' OR x_inscription_grp.niv_2='$groupe' OR x_inscription_grp.niv_3='$groupe')";

            }


    if ($existe == '0') {

        $requete = "SELECT `code_admission`,CONCAT(`nom`,' ',`prenom`) as name,'' as Pointage,'' as cat,'' as cat_ens,'' as cat_ens,'' as cat_re,'' as cat_f
        FROM `x_inscription_grp` WHERE x_inscription_grp.promotion='$promotion' $concatenation";
    
    }
    else {
        $requete = "SELECT `id_admission` as code_admission,concat(`nom`,' ',`prénom`) as name,date_format(`heure_pointage`,'%H:%i') as Pointage,`categorie` as cat,`categorie_enseig` as cat_ens,`categorie_si` as cat_re,`categorie_enseig` as cat_ens,`categorie_f` as cat_f
        FROM `xseance_absences` WHERE `id_séance`=$seance";
    }
    $stmt = $this->em->getConnection()->prepare($requete);
    $newstmt = $stmt->executeQuery();   
    $etudiants = $newstmt->fetchAll();

    $html = $this->render('assiduite/table/datatable_etud.html.twig', [
        'etudiants' => $etudiants,
   ])->getContent();
   return new JsonResponse($html);

 }
 }
//------------------------------------------------------etuddetails----------------------------------------------------------------------
#[Route('/Etud_details', name: 'Etud_details')]
 public function Etud_details(Request $request)
    
    
 { 
    if($request->isXmlHttpRequest()) {
        $seance = $request->request->get('seance');  
        $etudiant = $request->request->get('etudiant');  
        $xseance_abs = $this->em->getRepository(XseanceAbsences::class)->findBy(['ID_Séance'=>$seance,'ID_Admission'=>$etudiant]);
        // dd($xseance_abs[0]->getMotif());
        $xseance_motif = $this->em->getRepository(XseanceMotifAbs::class)->findBy(['id'=>$xseance_abs[0]->getMotif()]);
        // dd($xseance_abs);
        $html = $this->render('assiduite/modals/etud_det_cont.html.twig', [
            'etudiants' => $xseance_abs,
            'xseance_motif' => $xseance_motif,

       ])->getContent();
       return new JsonResponse($html);
   
    }
 }
//------------------------------------------------------etuddetails----------------------------------------------------------------------
#[Route('/Etud_details_valide', name: 'Etud_details_valide')]
 public function Etud_details_valide(Request $request)
    
    
 { 
    if($request->isXmlHttpRequest()) {
        $seance = $request->request->get('seance');  
        $etudiant = $request->request->get('etudiant');  
        $categorie_ens = $request->request->get('cat_ens');  
        $motif = $request->request->get('motif_abs');  
        $obs = $request->request->get('obs');  
        $justif = $request->request->get('justif');  
        $xseance_abs = $this->em->getRepository(XseanceAbsences::class)->findOneBy(['ID_Séance'=>$seance,'ID_Admission'=>$etudiant]);
        $xseance_abs->setCategorieEnseig($categorie_ens);
        $xseance_abs->setObs($obs);
        $xseance_abs->setJustifier($justif);
        $xseance_abs->setMotif($motif);
        $this->em->persist($xseance_abs);
    
        $this->em->flush();
    
        
       return new JsonResponse('ok');
   
    }
 }
//------------------------------------------------------etuddetails----------------------------------------------------------------------
#[Route('/Etud_pointage', name: 'Etud_pointage')]
 public function Etud_pointage(Request $request)
    
    
 { 
    if($request->isXmlHttpRequest()) {
        $promo = $request->request->get('promo');  
        $date = $request->request->get('date');  
        $hd = $request->request->get('hd'); 
        $datef = "$date $hd";
        $hd1 = date('Y-m-d H:i:s', strtotime($datef. ' -15 minutes')); 
        $hd2 = date('Y-m-d H:i:s', strtotime($datef. ' +45 minutes'));
      
       
        $query = "SELECT x_inscription_grp.code_admission,userinfo.userid, CONCAT(x_inscription_grp.nom,x_inscription_grp.prenom) as name,
                  checkinout.checktime,checkinout.sn
          FROM `userinfo` 
                    INNER JOIN checkinout ON userinfo.userid=checkinout.userid
                    INNER JOIN x_inscription_grp ON x_inscription_grp.code_admission=userinfo.street
                    WHERE x_inscription_grp.promotion=$promo  AND checkinout.checktime BETWEEN '$hd1' AND '$hd2'";
                
       $pointages =  self::execute($query,$this->em);
       $query_s = "SELECT iseance_salle.id_pointeuse,psalles.abreviation FROM
                  `psalles` INNER JOIN iseance_salle ON iseance_salle.code_salle=psalles.code";
       $salles =  self::execute($query_s,$this->em);
    
        
       $html = $this->render('assiduite/table/datatable_pointage.html.twig', [
        'salles' => $salles,
        'pointages' => $pointages,

   ])->getContent();
   return new JsonResponse($html);
   
    }
 }
 //{#---------------------------------------------------------------------------------------------------------------------------------#}   
 
 
 #[Route('/traitement_assiduite', name: 'traitement_assiduite')]
 public function traitement_assiduite(Request $request)
 {   
    if($request->isXmlHttpRequest()) {

      
        // $promotion = $request->request->get('promotion');  
        // $module = $request->request->get('module');  
        $seance = $request->request->get('seance');  
        // $groupe = $request->request->get('groupe');  
        // $salle = $request->request->get('sale');  
        $date = $request->request->get('date');  
        // $hd = $request->request->get('hd');
        $type = $request->request->get('type');
        $requete_seance = "SELECT promotion,module,groupe,heure_debut,heure_fin,code_salle FROM `v_seance` WHERE code_seance=$seance";
        $seance_des =  self::execute($requete_seance,$this->em);
        $promotion = $seance_des[0]['promotion'];
        $module = $seance_des[0]['module'];
        $groupe = $seance_des[0]['groupe'];
        $hd = $seance_des[0]['heure_debut'];
        $salle = $seance_des[0]['code_salle'];
        if(empty($groupe)) {
            $groupe = 'empty';
        }
        $newDateD = date('Y-m-d H:i:s', strtotime($date.' '.$hd. ' -15 minutes'));
        $newDateF = date('Y-m-d H:i:s', strtotime($date.' '.$hd. ' +50 minutes'));

        $liste_etudiant_P = [];
        $liste_etudiants = [];
        $data = self::pointeuse_ip($salle,'traite',$date,$this->em);

        $requete_P = self::traitement_P($promotion,$module,$groupe,$date);

                       $etudiants_p =  self::execute($requete_P,$this->em);
                     
                             foreach($etudiants_p as $i) {
                                    array_push($liste_etudiant_P, ["id_admission" => $i['adm'], "nom" => $i['nom'], "prenom" => $i['prenom'], "categorie" => $i['categorie'],"pointage" => $i['pointage'],"date" => $i['date']]);
                                    array_push($liste_etudiants, $i['adm']);
                                                   }
        $requete_abcd = self::traitement_abcd($promotion,$date,$newDateD,$newDateF,$salle,$groupe);
                      
                        $etudiants_abcd = self::execute($requete_abcd,$this->em);
                            foreach($etudiants_abcd as $i) {
                            array_push($liste_etudiant_P, ["id_admission" => $i['adm'], "nom" => $i['nom'], "prenom" => $i['prenom'], "categorie" => $i['categorie'],"pointage" => $i['pointage'],"date" => $i['date']]);
                            array_push($liste_etudiants, "'".$i['adm']."'");

                                                       }
                                                       $liste_etudiants = implode(",", $liste_etudiants);
                            
        $requete_d = self::traitement_d($liste_etudiants,$promotion,$groupe,$date);

                       $etudiants_d =  self::execute($requete_d,$this->em);

                        foreach($etudiants_d as $i) {
                            array_push($liste_etudiant_P, ["id_admission" => $i['adm'], "nom" => $i['nom'], "prenom" => $i['prenom'], "categorie" => $i['categorie'],"pointage" => $i['pointage'],"date" => $i['date']]);

                                                       }

        if ($type == "traite") {
            $xseance = self::insert_xseance($seance,$this->em);
            $xseances = self::insert_xseance_absc($liste_etudiant_P,$seance,$this->em);                                                             
                          }
        else {
            $retraite_seance = self::retraite_seance($liste_etudiant_P,$seance,$this->em);                                                       
                             }
        $requete_count = self::coun_categorie($seance);
        $counts =  self::execute($requete_count,$this->em);
   
        $html = $this->render('assiduite/modals/count.html.twig', [
            'counts' => $counts,
       ])->getContent();
       return new JsonResponse($seance);             
       
       

}
}



#[Route('/count_seance/{seance}', name: 'count_seance')]
public function count_seance(Request $request,$seance)
{  
   
    $requete_count = self::coun_categorie($seance);
    $counts =  self::execute($requete_count,$this->em);

    $html = $this->render('assiduite/modals/count.html.twig', [
        'counts' => $counts,
   ])->getContent();
   return new JsonResponse($html);    
}

#[Route('/remove_seance/{seance}', name: 'remove_seance')]
public function remove_seance(Request $request,$seance)
{  
   
    $counts =  self::remove($seance,$this->em);

   return new JsonResponse("html");    
}

#[Route('/exist_seance/{seance}', name: 'exist_seance')]
public function exist_seance(Request $request,$seance)
{  
   
    $xseance = $this->em->getRepository(Xseance::class)->findOneBy(['ID_Séance'=>$seance]);
    $xseance->setExiste(1);
    $this->em->persist($xseance);

    $this->em->flush();

   return new JsonResponse("html");    
}

#[Route('/sign_seance/{seance}', name: 'sign_seance')]
public function sign_seance(Request $request,$seance)
{  
   
    $xseance = $this->em->getRepository(Xseance::class)->findOneBy(['ID_Séance'=>$seance]);
    $xseance->setSigné(1);
    $this->em->persist($xseance);

    $this->em->flush();
   return new JsonResponse("html");    

}

#[Route('/cancel_seance/{seance}', name: 'cancel_seance')]
public function cancel_seance(Request $request,$seance)
{  
  
    $xseance = $this->em->getRepository(Xseance::class)->findOneBy(['ID_Séance'=>$seance]);
    $xseance->setAnnulée(1);
    $this->em->persist($xseance);

    $this->em->flush(); 
   return new JsonResponse("html");    

}
#[Route('/dever_seance/{seance}', name: 'dever_seance')]
public function dever_seance(Request $request,$seance)
{  
  
    $xseance = $this->em->getRepository(Xseance::class)->findOneBy(['ID_Séance'=>$seance]);
    $xseance->setStatut(1);
    $this->em->persist($xseance);

    $this->em->flush(); 
   return new JsonResponse("html");    

}
#[Route('/lock_seance/{seance}', name: 'lock_seance')]
public function lock_seance(Request $request,$seance)
{  
  
    $xseance = $this->em->getRepository(Xseance::class)->findOneBy(['ID_Séance'=>$seance]);
    $xseance->setStatut(2);
    $this->em->persist($xseance);

    $this->em->flush(); 
   return new JsonResponse("html");    

}

#[Route('/modifier_salle/{seance}/{salle}', name: 'modifier_salle')]
public function modifier_salle(Request $request,$seance,$salle)
{  
    $seance = $this->em->getRepository(PlEmptime::class)->findOneBy(['id'=>$seance]);
    $seance->setXsalle($this->em->getRepository(PSalles::class)->findOneBy(['id'=>$salle]));
    $this->em->persist($seance);

    $this->em->flush();   
   return new JsonResponse("html");    

}
#[Route('/zkteco', name: 'zkteco')]
public function zk(Request $request)
{  
  
    $zk = new \ZKLib('172.20.10.92', 4370, 'udp');
    
    $zk->connect();
    $zk->disableDevice();
    
    
    // $attendace = $zk->getAttendance();
    // dd($attendace);
   return new JsonResponse("html");    

}

#[Route('/insert', name: 'insert')]
public function insert(Request $request)
{  
  
  

                                 
  
                                    $zk = new \ZKLibrary("172.20.4.1", 4370);

        $zk->connect();
        // $attendaces = $zk->getAttendance();
      
        $sn = $zk->getSerialNumber();
        
      
       


    
        foreach ($attendaces as $att ){
            $sqluser="SELECT userinfo.USERID,userinfo.USERID as exis
             from userinfo where Badgenumber='$att[1]'";

            // $stmuser = $this->getDoctrine()->getConnection()->prepare($sqluser);
            // $stmuser->execute();    
            // $seauser = $stmuser->fetchAll();
            $seauser = self::execute($sqluser,$this->em);
            foreach($seauser as $user){     
                
              //  if ($user->exis) {
                            //  if( $att[3] > $time){     
                                    try {

                                        $req = "INSERT INTO `checkinout2`(`USERID`, `CHECKTIME`, `CHECKTYPE`,`sn`,`Memoinfo`) 
                                        VALUES ('" .$user->USERID. "','" .$att[3]. "','" .$att[2]. "','" .$sn. "','')";
                                        $stmt = $this->em->getConnection()->prepare($req);
                                        $stmt->execute();  
                                        $all ='insert succesufly'; 

                                    } catch (\Throwable $th) {

                                        $all ='insert not succesufly'; 
                                    }          
            
             
                           //    }  
                                // else{
                                //     $all='not insert succesufly';
                                // }
            
      //  }

}
}

       
           // $all='insert succesufly';
      
        return new JsonResponse($all);        

       
// is null twig ?


}

#[Route('/parlot', name: 'parlot')]
public function parlot(Request $request)
{  
  
        $hd = $request->request->get('hd');  
        $hf = $request->request->get('hf'); 

        $TodayDate= new \DateTime(); 
        // $date= date_format($TodayDate, 'Y-m-d');
        $date = '2022-10-03';
        $requete="SELECT v_seance.code_seance as cod,TIME_FORMAT(v_seance.heure_debut, '%H:%i') AS heur_db , TIME_FORMAT(v_seance.heure_fin, '%H:%i') AS heur_fin,ac_etablissement.abreviation as eta ,ac_formation.abreviation as form ,ac_promotion.designation as pro ,v_seance.groupe, psalles.abreviation AS salle, ac_module.designation as module1, ac_element.designation as element, penseignant.nom, penseignant.prenom,xseance.ID_Séance as exis
        FROM v_seance
        LEFT JOIN xseance ON xseance.ID_Séance=v_seance.code_seance
        INNER JOIN ac_promotion ON ac_promotion.id=v_seance.promotion
        INNER JOIN ac_formation ON ac_formation.id=v_seance.formation
        INNER JOIN ac_etablissement ON ac_etablissement.id=v_seance.etablissement
        LEFT JOIN ac_module ON ac_module.code=v_seance.module
        LEFT JOIN ac_element ON ac_element.code=v_seance.element
        left JOIN psalles ON psalles.code=v_seance.code_salle
        LEFT JOIN penseignant ON penseignant.code=v_seance.enseignant
        WHERE v_seance.date_seance like '$date%' AND v_seance.heure_debut>='$hd' AND v_seance.heure_fin<='$hf' ORDER BY v_seance.heure_debut";
        $seances =  self::execute($requete,$this->em);
        $html = $this->render('assiduite/table/datatable_parlot.html.twig', [
            'seances' => $seances,
       ])->getContent();

   return new JsonResponse($html);    

}



    //////////////////////////////////////////////////assiduite_pointeuse/////////////////////////////////////

    
#[Route('/pointeuse_aff/{idsalle}', name: 'pointeuse_aff')]
public function pointeuse_aff(Request $request, $idsalle)
{  
  
        
        $TodayDate= new \DateTime(); 

        $requete="SELECT DISTINCT iseance_salle.code_salle,psalles.designation,machines.sn,machines.IP 
        FROM `psalles` 
        INNER JOIN iseance_salle ON iseance_salle.code_salle=psalles.code
        INNER JOIN machines ON iseance_salle.id_pointeuse=machines.sn where psalles.code='$idsalle'";
        $salles =  self::execute($requete,$this->em);
        $html = $this->render('assiduite/table/datatable_pointeuse.html.twig', [
            'salles' => $salles,
       ])->getContent();
       return new JsonResponse($html);

}


#[Route('/pointeuse_connect/{idsalle}', name: 'pointeuse_connect')]
public function pointeuse_connect(Request $request, $idsalle)
{  
    $zk = new \ZKLib($idsalle, 4370, 'udp');
    if ($zk->connect() == 'true') {
        $statut = "true";
    }
    else {
        $statut = "false";

    }
    // $ret = $zk->connect();
    // $zk->disableDevice();
    return new JsonResponse($statut);
   

    
}

#[Route('/pointeuse_download/{idsalle}/{date}', name: 'pointeuse_download')]
public function pointeuse_download(Request $request, $idsalle,$date)
{  
   
    $data = self::pointeuse_ip($idsalle,'import',$date,$this->em);
    
    return new JsonResponse('ok');
   

    
}

#[Route('/pointeuse_user/{idsalle}', name: 'pointeuse_user')]
public function pointeuse_user(Request $request, $idsalle)
{  
    $zk = new \ZKLib($idsalle, 4370, 'udp');
    if ($zk->connect() == 'true') {
        $statut = "device connected";
    }
    else {
        $statut = "device is not connected";

    }
    $ret = $zk->connect();
    $zk->disableDevice();
    $user_device = $zk->getUser();
    $users = "SELECT * FROM `userinfo`  WHERE street not like 'F%' ORDER BY `badgenumber` ASC";
    $users =  self::execute($users,$this->em);
    $html = $this->render('assiduite/table/datatable_pointeuse_user.html.twig', [
        'users' => $users,
        'device_users' => $user_device,
   ])->getContent();
   return new JsonResponse($html);
}

#[Route('/pointeuse_att/{idsalle}/{date}', name: 'pointeuse_att')]
    public function pointeuse_att(Request $request, $idsalle,$date)
    {  
        ini_set('memory_limit', '2G'); // or you could use 1G


        // $zk = new \ZKLib($idsalle, 4370, 'udp');
        $zk = new \ZKLib('172.20.7.208', 4370, 'udp');
        if ($zk->connect() == 'true') {
            $statut = "device connected";
        }
        else {
            $statut = "device is not connected";
    
        }
        $ret = $zk->connect();
        $zk->disableDevice();
        // $user_device = $zk->getAttendance($date);
        // $user_device = $zk->getAttendance('2022-11-07');    
        $user_device = $zk->getAttendance_date($date,$date); 
        if (count($user_device) > 0) {
            $user_device = $zk->getAttendance_date($date,$date); 

        }
        else{
        $user_device = ""; 

        }

        // dd(count($user_device));
        $users = "SELECT * FROM `userinfo`  
         ORDER BY `badgenumber` ASC";
        // WHERE street not like 'F%' ORDER BY `badgenumber` ASC";
        $users =  self::execute($users,$this->em);
        $html = $this->render('assiduite/table/datatable_pointeuse_att.html.twig', [
            'users' => $users,
            'device_users' => $user_device,
       ])->getContent();

   return new JsonResponse($html);

    }


// #[Route('/pointeuse_insert/{idsalle}/{date}', name: 'pointeuse_insert')]
    static function pointeuse_insert($idsalle,$sn,$date,$em)
    {  
        // set_time_limit(1500);   
    $requete= "SELECT DATE_FORMAT(checktime, '%Y-%m-%d') as date FROM `checkinout` WHERE sn='$sn' ORDER BY `checkinout`.`checktime` DESC LIMIT 1";
    $pointage =  self::execute($requete,$em);
    // $date = date('Y-m-d', strtotime('-1 day', strtotime($pointage[0]['date'])));
    // dd($pointage[0]['date']);
   
    $zk = new \ZKLib("$idsalle", 4370, "udp");

    if ($zk->connect() == 'true') {
                $statut = "device connected";
                    $ret = $zk->connect();
                    $zk->disableDevice();
                        $attendance = $zk->getAttendance($date);
            // $attendance = $zk->getAttendance_date($date,$date);
                    // if (count($attendance) > 0) {
                    //     $attendance = array_reverse($attendance, true);
                    //     foreach ($attendance as $attItem) {
                    //     $user = $em->getRepository(Userinfo::class)->findOneBy(['Badgenumber'=>$attItem['id']]);
                    //     if ($user) {
                    //         $date_ = new \DateTime($attItem["timestamp"]);
                    //         // $check = $this->em->getRepository(checkinout::class)->findBy(['USERID'=>$user->getUSERID(), 'CHECKTIME' => $date]);
                    //         $checkinout = new Checkinout;
                    //         $checkinout->setUSERID($user->getUSERID());
                    //         $checkinout->setSn($sn);
                    //         $checkinout->setCHECKTIME($date_);
                    //         $checkinout->setMemoinfo("test4");
                    //         $em->persist($checkinout);
                
                
                        
                    //     }
                    // }
                    //     $em->flush();
            
                
                    // }
            $statut = "'".$idsalle."'device is connected";

        }
        else {
            // array_push($pointeuse_status, ["eta" => "'".$idsalle."'device is not connected"]);

            $statut = "'".$idsalle."'device is not connected";

        }
        // dd($pointeuse_status);
        
   
       

    return $statut; 

   
    }

    #[Route('/aff_situation', name: 'aff_situation')]
    public function aff_situation(Request $request)
    {  
       $etudiant = $request->request->get('etudiant');  
       $dated = $request->request->get('dated');  
       $datef = $request->request->get('datef');  

       $requete = "SELECT * FROM `checkinout` 
       INNER JOIN userinfo ON userinfo.userid=checkinout.userid
       INNER JOIN machines ON machines.sn=checkinout.sn
        WHERE checktime BETWEEN '$dated' AND '$datef' AND street ='$etudiant'";
        $salle_requete = "SELECT * FROM `psalles` INNER JOIN iseance_salle ON iseance_salle.code_salle=psalles.code";
        $pointages =  self::execute($requete,$this->em);
        $salles =  self::execute($salle_requete,$this->em);

        $html = $this->render('assiduite/table/datatable_situation.html.twig', [
            'pointages' => $pointages,
            'salles' => $salles,
       ])->getContent();
       return new JsonResponse($html);

    }


    #[Route('/etud_aff_situation/{etudiant}', name: 'etud_aff_situation')]
    public function etud_aff_situation(Request $request, $etudiant)
    {  
        $requete = "SELECT * FROM `x_inscription_grp` WHERE promotion=$etudiant";
         $etudiants =  self::execute($requete,$this->em);
    $data = self::dropdownsituation($etudiants,'etudiant');
    return new JsonResponse($data);

    }

   
           
    #[Route('/generate_extraction', name: 'assiduite_assiduites_generate_extraction')]
    public function generate_extraction(Request $request): Response
    {

        $from = $_GET['From'];
        $to = $_GET['To'];
        $service = $_GET['Service'];
        $formation = $_GET['formation'];
        $promotion = $_GET['promotion'];
        $tou = $_GET['Tou'];
        $type = $_GET['type'];
        $TodayDate = date("Y-m-d");
       
        if ($type == 'stage') {
            $requete2 = "and v_seance.nature_des='ST'";
        }
        else{
            $requete2 = '';
        }
      
             
        if ($tou =='tous') {
         $requete = "and v_seance.etablissement='$service'";
        }
       elseif ($tou =='promo') {
         $requete = "and  v_seance.promotion='$promotion'";
       }
        
        else{
          
          $requete = "";
          // and ac_formation.abreviation='$formation' and ac_promotion.abreviation='$promotion'
        }
      
       $spreadsheet = new Spreadsheet();

     $sql="SELECT xseance_absences.ID_Admission,xseance_absences.Nom,xseance_absences.Prénom,xseance.ID_Séance,xseance.ID_Module as module,pnature_epreuve.abreviation as typ,xseance.Date_Séance,xseance_absences.Heure_Pointage,xseance_absences.Categorie,xseance_absences.Categorie_Enseig,xseance.Heure_Debut as hd,xseance.Heure_Fin as hf ,
     v_seance.etablissement_des as etablissement,v_seance.formation_des as formation,v_seance.promotion_des AS 
          promotion,v_seance.element_des as element,v_seance.module
     
          FROM `xseance_absences`
          INNER JOIN xseance ON xseance.ID_Séance=xseance_absences.ID_Séance    
          INNER JOIN v_seance ON xseance.ID_Séance=v_seance.code_seance
         
          inner JOIN pnature_epreuve ON v_seance.type_seance=pnature_epreuve.code
          
     WHERE xseance.Date_Séance>='$from' and xseance.Date_Séance<='$to' AND pnature_epreuve.Absence=1 
     AND (xseance.Annulée=0 or xseance.Annulée is NULL ) $requete $requete2";  
   $VHR =  self::execute($sql,$this->em);

 
       // echo $tou;
     


   $sheet = $spreadsheet->getActiveSheet();
   
   $sheet->setCellValue('A1', 'ID_Admission');
   $sheet->setCellValue('B1', 'Nom');
   $sheet->setCellValue('C1', 'Prenom');
   $sheet->setCellValue('D1', 'Etablisement');
   $sheet->setCellValue('E1', 'Formation');
   $sheet->setCellValue('F1', 'Promotion');
   $sheet->setCellValue('G1', 'Type');
   $sheet->setCellValue('H1', 'Element');
   $sheet->setCellValue('I1', 'Module');
   $sheet->setCellValue('J1', 'ID_seance');
   $sheet->setCellValue('K1', 'Date seance');
   $sheet->setCellValue('L1', 'Heure Pointage');
   $sheet->setCellValue('M1', 'Categorie');
   $sheet->setCellValue('N1', 'Categorie ens');
   $sheet->setCellValue('O1', 'Heure debut');
   $sheet->setCellValue('P1', 'Heure fin');
  

   $sheet->setTitle("My First Worksheet");
   $count = 2;

   foreach($VHR as $VH){
     $module = $VH['module'];
     $sqlm="SELECT * FROM `ac_module` WHERE `code`='$module' ";  
    
  
       $VHRm =  self::execute($sqlm,$this->em);

       foreach($VHRm as $m){

         $module = $m['designation'];



       }
   

   $sheet->setCellValue('A' . $count, $VH['ID_Admission']);
   $sheet->setCellValue('B' . $count, $VH['Nom']);
   $sheet->setCellValue('C' . $count, $VH['Prénom']);
   $sheet->setCellValue('D' . $count, $VH['etablissement']);
   $sheet->setCellValue('E' . $count, $VH['formation']);
   $sheet->setCellValue('F' . $count, $VH['promotion']);
   $sheet->setCellValue('G' . $count, $VH['typ']);
   $sheet->setCellValue('H' . $count, $VH['element']);
   $sheet->setCellValue('I' . $count, $module);
   $sheet->setCellValue('J' . $count, $VH['ID_Séance']);
   $sheet->setCellValue('K' . $count, $VH['Date_Séance']);
   $sheet->setCellValue('L' . $count, $VH['Heure_Pointage']);
   $sheet->setCellValue('M' . $count, $VH['Categorie']);
   $sheet->setCellValue('N' . $count, $VH['Categorie_Enseig']);
   $sheet->setCellValue('O' . $count, $VH['hd']);
   $sheet->setCellValue('P' . $count, $VH['hf']);
 

$count = $count + 1;

   }

    // Create your Office 2007 Excel (XLSX Format)
    $writer = new Xlsx($spreadsheet);
   
    // Create a Temporary file in the system
    $fileName = 'extraction'.$TodayDate.'.xlsx';
    $temp_file = tempnam(sys_get_temp_dir(), $fileName);
    
    // Create the excel file in the tmp directory of the system
    $writer->save($temp_file);
    
    // Return the excel file as an attachment
    return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);




    }
           
    #[Route('/regularisation_date', name: 'assiduite_assiduites_regularisation_excel')]
    public function regularisation_date(Request $request): Response
    {
        $file = $_GET['file'];
        

        if(!$file) {
            return $this->redirectToRoute('reguldate');
        }


        $xlsx = new Xlsxx;
        $xlsx->setLoadSheetsOnly(["Feuil1", $file]);
        $spreadsheet = $xlsx->load($file);
        $row = $spreadsheet->getActiveSheet()->removeRow(1);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        // dd($sheetData);
        $TodayDate= new \DateTime();
        $date= date_format($TodayDate, 'Y-m-d');
        $dat= date_format($TodayDate, 'Y-m-d H:i');
            foreach($sheetData as $sheet) {
              
                $insert='UPDATE `xseance_absences`
                INNER JOIN xseance  ON xseance.ID_Séance=xseance_absences.ID_Séance
                SET `Categorie`="'.$sheet[10].'",`Categorie_Enseig`="",`Obs`="'.$sheet[12].'" 
                
                WHERE xseance_absences.ID_Admission="'.$sheet[0].'" AND  xseance.Date_Séance BETWEEN "'.$sheet[7].'" AND "'.$sheet[8].'"' ;
                // dd($insert);
                // $execute =  self::execute($insert,$this->em);
                $stmt = $this->em->getConnection()->prepare($insert);
                $newstmt = $stmt->executeQuery();   


               
                     


            

            }

            return new Response("regularisation generated succesfully");
      
    }

    
   
           
    #[Route('/regularisation_seance', name: 'assiduite_assiduites_regularisation_seance')]
    public function regularisation_seance(Request $request): Response
    {
        $file = $_GET['file'];
        

        if(!$file) {
            return $this->redirectToRoute('reguldate');
        }


        $xlsx = new Xlsxx;
        $xlsx->setLoadSheetsOnly(["Feuil1", $file]);
        $spreadsheet = $xlsx->load($file);
        $row = $spreadsheet->getActiveSheet()->removeRow(1);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        // dd($sheetData);
        $TodayDate= new \DateTime();
        $date= date_format($TodayDate, 'Y-m-d');
        $dat= date_format($TodayDate, 'Y-m-d H:i');
            foreach($sheetData as $sheet) {
              
                $insert='UPDATE xseance_absences SET categorie="'.$sheet[10].'", categorie_enseig="'.$sheet[11].'",obs="'.$sheet[12].'" 
                WHERE `id_admission`="'.$sheet[0].'" AND `id_séance`="'.$sheet[7].'"';
                
                // dd($insert);
                // $execute =  self::execute($insert,$this->em);
                $stmt = $this->em->getConnection()->prepare($insert);
                $newstmt = $stmt->executeQuery();   


               
                     


            

            }

            return new Response("regularisation generated succesfully");
      
    }

    

  
    #[Route('/bordaff/{action}', name: 'bordaff')]
    public function bordaff(Request $request,$action)
    {  
        $TodayDate= new \DateTime(); 
        $date= date_format($TodayDate, 'Y-m-d');
            if ($action == "nt") {
                $statut = '';
             }
             elseif ($action == "nv") {
                 $statut = 'AND xseance.Statut=1';
             }
             else {
                 $statut = 'AND xseance.Statut=2';
             }
             $sql="SELECT ac_etablissement.abreviation as eta ,ac_formation.abreviation as form ,ac_promotion.designation as pro,
             xseance.ID_Séance,xseance.Date_Séance,xseance.Heure_Debut,xseance.Heure_Fin,xseance.Groupe
             FROM xseance
             INNER JOIN ac_promotion ON ac_promotion.code=xseance.ID_Promotion
             INNER JOIN ac_formation  ON ac_formation.id=ac_promotion.formation_id
             INNER JOIN ac_etablissement  ON ac_etablissement.id=ac_formation.etablissement_id
             
            WHERE xseance.Date_Séance>='2022-09-01' AND xseance.Date_Séance<='$date' $statut  and (xseance.Annulée is NULL or xseance.Annulée=0)";
              $seances =  self::execute($sql,$this->em);
              
              if ($action == "ns") {
                foreach($seances as $key => $u){
                    if(file_exists('\\\172.20.0.54\uiass\pdf\\'.$u['ID_Séance'].'.pdf')==1){
                        // $seances = array_push({"etab"=>".$u->eta."});
                        unset($seances[$key]);



                    }
              }
              }
      
        $html = $this->render('assiduite/table/datatable_bord.html.twig', [
            'seances' => $seances,
       ])->getContent();
       return new JsonResponse($html);

    }
  

    // #[Route('/pointeu_ip/{salle}/{type}/{date}', name: 'pointeuse_ip')]
    static function pointeuse_ip($salle,$type,$date,$em)
    {  
        if ($type == 'traite') {
            // dd('traiter');   
            $pointeuse_status = [];
            
            $requete = "SELECT distinct iseance_salle.code_salle,psalles.designation,machines.sn,machines.IP 
            FROM `psalles` 
            INNER JOIN iseance_salle ON iseance_salle.code_salle=psalles.code 
            INNER JOIN machines ON iseance_salle.id_pointeuse=machines.sn
            where machines.IP  in  ('172.20.10.2','172.20.10.3','172.20.10.4','172.20.10.5','172.20.10.6','172.20.10.7'
                        ,'172.20.10.8','172.20.10.9','172.20.10.10','172.20.10.11','172.20.10.12','172.20.10.13','172.20.10.14')";
            // --  where psalles.code='$salle'";
            $ip =  self::execute($requete,$em);
            foreach ($ip as $ips) {
                $data = self::pointeuse_insert($ips['IP'],$ips['sn'],$date,$em);
                array_push($pointeuse_status, ["eta" => $data]);

        }
        dd($pointeuse_status);
        }
        else {
            dd('else');

            $zk = new \ZKLib("$salle", 4370, "udp");

            if ($zk->connect() == 'true') {
                $statut = "device connected";
                $ret = $zk->connect();
                $sn = $zk->serialNumber();
                $zk->disableDevice();
                    $attendance = $zk->getAttendance($date);
                    // $attendance = $zk->getAttendance_date($date,$date);
                    if (count($attendance) > 0) {
                        $attendance = array_reverse($attendance, true);
                        foreach ($attendance as $attItem) {
                        $user = $em->getRepository(Userinfo::class)->findOneBy(['Badgenumber'=>$attItem['id']]);
                        if ($user) {
                            $date_ = new \DateTime($attItem["timestamp"]);
                            // $check = $this->em->getRepository(checkinout::class)->findBy(['USERID'=>$user->getUSERID(), 'CHECKTIME' => $date]);
                               $checkinout = new Checkinout;
                               $checkinout->setUSERID($user->getUSERID());
                               $checkinout->setSn($sn);
                               $checkinout->setCHECKTIME($date_);
                               $checkinout->setMemoinfo("test4");
                               $em->persist($checkinout);
                
                
                          
                        }
                    }
                        $em->flush();
                        $statut = "insert avec success";
            
                  
                    }
            }
            else {
                $statut = "device is not connected.";
        
            }
           
        }
        
        return $type;

    }


  

}
