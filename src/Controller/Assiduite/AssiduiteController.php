<?php

namespace App\Controller\Assiduite;

use Mpdf\Mpdf;
use App\Entity\PSalles;
use App\Entity\Xseance;
use App\Entity\AcFormation;
use App\Entity\AcPromotion;
use App\Entity\TOperationdet;
use App\Entity\AcEtablissement;
use App\Controller\ApiController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;


#[Route('/assiduite/assiduites')]
class AssiduiteController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'assiduite_assiduites_index')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_index', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
        $formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
        $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
        $salles = $this->em->getRepository(PSalles::class)->findAll();
        // $test = $this->em->getRepository(Xseance::class)->findseance(1);
        // dd($test);



        return $this->render('assiduite/index.html.twig', [
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
            'salles' => $salles,
            'operations' => $operations,


        ]);
    }

    #[Route('/stage', name: 'assiduite_assiduites_Stage')]
    public function stage(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_Stage', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
        $formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
        $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
        $salles = $this->em->getRepository(PSalles::class)->findAll();
        // $test = $this->em->getRepository(Xseance::class)->findseance(1);
        // dd($test);



        return $this->render('assiduite/stage.html.twig', [
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
            'salles' => $salles,
            'operations' => $operations,


        ]);
    }


    #[Route('/pointeuse', name: 'assiduite_assiduites_pointeuse')]
    public function pointeuse(Request $request): Response
    {
      
//connect to database in php?



$operations = ApiController::check($this->getUser(), 'assiduite_assiduites_pointeuse', $this->em, $request);
if(!$operations) {
    return $this->render("errors/403.html.twig");
}
$etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
$formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
$promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
$salles = $this->em->getRepository(PSalles::class)->findAll();


        return $this->render('assiduite/pointeuse.html.twig', [
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
            'salles' => $salles,
            'operations' => $operations,


        ]);
    }

    #[Route('/situation_pointage', name: 'assiduite_assiduites_situation')]
    public function situation_pointage(Request $request): Response
    {
      
//connect to database in php?



$operations = ApiController::check($this->getUser(), 'assiduite_assiduites_pointeuse', $this->em, $request);
if(!$operations) {
    return $this->render("errors/403.html.twig");
}
$etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
$formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
$promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
$requete = "SELECT * FROM `x_inscription_grp` WHERE `promotion_code`='PRM0000001'";
$stmt = $this->em->getConnection()->prepare($requete);
$newstmt = $stmt->executeQuery();   
$result = $newstmt->fetchAll();


        return $this->render('assiduite/situation.html.twig', [
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
            'etudiants' => $result,
            'operations' => $operations,


        ]);
    }

    #[Route('/dashbord', name: 'assiduite_assiduites_dashbord')]
    public function dashbord(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_dashbord', $this->em, $request);
if(!$operations) {
    return $this->render("errors/403.html.twig");
}
        return $this->render('assiduite/dashbord.html.twig', [
            'operations' => $operations,
          
        ]);
      
    }



    #[Route('/situation_presentiel', name: 'assiduite_assiduites_presentiel')]
    public function situation_presentiel(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_dashbord', $this->em, $request);
if(!$operations) {
    return $this->render("errors/403.html.twig");
}
$etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
$formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
$promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
$requete = "SELECT * FROM `x_inscription_grp` WHERE `promotion_code`='PRM0000001'";
$stmt = $this->em->getConnection()->prepare($requete);
$newstmt = $stmt->executeQuery();   
$result = $newstmt->fetchAll();


          
        return $this->render('assiduite/situation_presentiel.html.twig', [
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
            'etudiants' => $result,
            'operations' => $operations,
          
        ]);
      
    }



    #[Route('/regularisation', name: 'assiduite_assiduites_regularisation')]
    public function regularisation(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_regularisation', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('assiduite/regularisation.html.twig', [
            'operations' => $operations,
          
        ]);
      
    }

    #[Route('/extraction', name: 'assiduite_assiduites_extraction')]
    public function extraction(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_extraction', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }

        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
        $formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
        $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
       
        
               
                  
        return $this->render('assiduite/extraction.html.twig', [
            'operations' => $operations,
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
          
        ]);
      
    }

    
    #[Route('/extraction_stage', name: 'assiduite_assiduites_Stage_extraction')]
    public function extraction_stage(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'assiduite_assiduites_Stage_extraction', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }

        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1,'assiduite'=>1]);
        $formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>1,'assiduite'=>1]);
        $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>1, 'active' => 1],['id'=>'ASC']);
       
        
               
                  
        return $this->render('assiduite/extraction_stage.html.twig', [
            'operations' => $operations,
            'etablissements' => $etbalissements,
            'formations' => $formations,
            'promotions' => $promotions,
          
        ]);
      
    }
    
    #[Route('/salle/{sall}', name: 'salle')]
    public function salle(Request $request,$sall): Response
    {
         //     $hour = date('H:i:s');
    //  $hour = '2021-10-07';
    //     $date = date('Y-m-d');
    $Today= new \DateTime(); 
    $date =  $Today->format('Y-m-d');
    $hour = $Today->format('H:i:s');  
    // $date = '14:50:00';
     $salleinf="SELECT * FROM psalles where abreviation='$sall'";
     $stmts= $this->em->getConnection()->prepare($salleinf);
     $stmts = $stmts->executeQuery();    
     $seans = $stmts->fetchAll();
     foreach($seans as $sa){

       //  dd($sa->code);
       $code = $sa['code'];
         $salle="SELECT * FROM xseance_absences
         INNER JOIN xseance ON xseance.ID_Séance=xseance_absences.ID_Séance
         WHERE xseance.Date_Séance='$date' AND xseance.ID_Salle='$code' AND '$hour' BETWEEN xseance.Heure_Debut 
         AND xseance.Heure_Fin ORDER BY xseance_absences.Nom ASC";
         $stmt = $this->em->getConnection()->prepare($salle);
         $stmt = $stmt->executeQuery();    
         $sean = $stmt->fetchAll();
         $salle_="SELECT * FROM xseance_absences
         INNER JOIN xseance ON xseance.ID_Séance=xseance_absences.ID_Séance
         WHERE xseance.Date_Séance='$date' AND xseance.ID_Salle='$code' AND '$hour' BETWEEN xseance.Heure_Debut AND xseance.Heure_Fin ORDER BY xseance_absences.Nom DESC";
         $stmt_ = $this->em->getConnection()->prepare($salle_);
         $stmt_ = $stmt_->executeQuery();    
         $sean_ = $stmt_->fetchAll();
         


     }



    
   
   
    


     return $this->render('assiduite/salle.html.twig', [
 "salle" =>$sean,
 "sallee" =>$sean_ ]); 
      
    }

    
#[Route('/pdf_presentiel/{etudiant}', name: 'pdf_presentiel')]
public function pdf_presentiel($etudiant)
{
    ini_set("pcre.backtrack_limit", "50000000");

    // $semester = $_GET['sem'];
    $semester = 1;
    // $idadm = $_GET['idadm'];
    $idadm = $etudiant;
    // $idadm = 'ADM-FMA_MG00005097';

if ($semester =="1") {
    $dated = '2022-09-05';
}
else {
    $dated = '2022-02-07';
    $TodayDate= new \DateTime();
    $date= date_format($TodayDate, 'Y-m-d');
}

    $TodayDate= new \DateTime();
    $date= date_format($TodayDate, 'Y-m-d');

    $date3= date_format($TodayDate, 'Y-m-d');
    setlocale(LC_TIME, "fr_FR", "French");
    $date2 = strftime("%A %d %B %G", strtotime($date3)); 

    $modules="SELECT * FROM ac_module";
 
    $seanmodules =  ApiController::execute($modules,$this->em);




    $elements="SELECT * FROM `ac_element`";
  
    $seanelements =  ApiController::execute($elements,$this->em);


    $mysql="SELECT xseance.ID_Séance,semaine.nsemaine as Semaine,semaine.date_debut as DateD,semaine.date_fin as DateF,pnature_epreuve.abreviation,xseance.ID_Module as module,xseance.ID_Element as element,
      TIME_FORMAT(xseance.Heure_Debut, '%H:%i') as hd,TIME_FORMAT(xseance.Heure_Fin, '%H:%i') as hf,xseance.Date_Séance,time_to_sec(timediff(xseance.Heure_Fin, 
      xseance.Heure_Debut )) / 3600 as volume,xseance_absences.Categorie,xseance_absences.Categorie_Enseig 
      FROM xseance LEFT JOIN semaine ON xseance.semaine= semaine.id LEFT JOIN xseance_absences ON xseance.ID_Séance=xseance_absences.ID_Séance 
      LEFT JOIN pnature_epreuve ON pnature_epreuve.code=xseance.TypeSéance 
      WHERE xseance_absences.ID_Admission='$idadm' AND semaine.annee_s='2022/2023' 
      AND xseance.Date_Séance>='$dated' AND xseance.Date_Séance<='$date' AND (xseance.annulée is Null or xseance.annulée=0) ORDER BY xseance.Date_Séance";

   
      
        $sean =  ApiController::execute($mysql,$this->em);

        $mysql2="SELECT xseance_absences.ID_Admission,xseance.ID_Séance,xseance.ID_Module,semaines.Semaine,semaines.DateD,semaines.DateF,xseance_absences.Nom,xseance_absences.Prénom,xseance.Année_Lib 
        FROM xseance 
        
        INNER JOIN semaines ON xseance.semaine=semaines.Semaine  
        INNER JOIN xseance_absences ON xseance.ID_Séance=xseance_absences.ID_Séance
        WHERE xseance_absences.ID_Admission='$idadm'  AND semaines.anneés='2022/2023'  ORDER BY semaines.Semaine limit 1";


            $sean2 =  ApiController::execute($mysql2,$this->em);
            
            
            foreach($sean2 as $la){
                $semaine= $la['Semaine'];
               
                $anne=$la['Année_Lib'];
                                  }
                                  
            $mysql3="SELECT    
            x_inscription_grp.code_admission,ac_etablissement.designation as etablissement,ac_formation.designation as form,ac_promotion.designation as promo,x_inscription_grp.nom,x_inscription_grp.prenom
            
                        FROM x_inscription_grp  
                        
                        INNER JOIN ac_promotion ON x_inscription_grp.promotion_code=ac_promotion.code
                        INNER JOIN ac_formation ON ac_promotion.formation_id=ac_formation.id
                         INNER JOIN ac_etablissement ON ac_formation.etablissement_id=ac_etablissement.id
            
                        WHERE x_inscription_grp.code_admission='$idadm'";

                       
                        $sean3 =  ApiController::execute($mysql3,$this->em);

                        foreach($sean3 as $la3){
                           
                            $nom=          $la3['nom'];
                            $prenom=       $la3['prenom'];
                            $Etablisement= $la3['etablissement'];
                            $formation=    $la3['form'];
                            $promotion=    $la3['promo'];
                        }
                        
        $count=count($sean);
        
     
        $html = $this->render('assiduite/pdf/feuil2.html.twig' , [
            
                "count" =>$count,
                "etud" =>$sean,
                "date" =>$date2,
                "admission" => $idadm,
                "Nom" => $nom,
                "Prenom" => $prenom,
                "Etab" => $Etablisement,
                "for" => $formation,
                "pro" => $promotion,
                "semain" => $semaine,
                "annee" => $anne,
                "modules" => $seanmodules,
                "elements" => $seanelements
        ])->getContent();

        
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_top' => 90,
        ]);            
        $mpdf->SetTitle('Feuil');
        $mpdf->SetHTMLHeader(
            $this->render("assiduite/pdf/header_presentiel.html.twig"
            , [
                "count" =>$count,
                "etud" =>$sean,
                "date" =>$date2,
                "admission" => $idadm,
                "Nom" => $nom,
                "Prenom" => $prenom,
                "Etab" => $Etablisement,
                "for" => $formation,
                "pro" => $promotion,
                "semain" => $semaine,
                "annee" => $anne,
                "modules" => $seanmodules,
                "elements" => $seanelements
            ])->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output('fueil' , 'I');

}

#[Route('/pdf/{seance}', name: 'pdf_seance')]
public function generate_pdfH($seance)
{


$TodayDate= new \DateTime();
$date= date_format($TodayDate, 'Y-m-d');
setlocale(LC_TIME, "fr_FR", "French");
$date2 = strftime("%A %d %B %G", strtotime($date)); 
   //seance+date+prof
 
   $m= "SELECT v_seance.promotion_des as promotion,v_seance.formation_des as formation,v_seance.etablissement_des as etablisement,
   xseance.ID_Séance,v_seance.nature_des,xseance.Groupe,v_seance.salle_des AS salle, v_seance.module as module, 
   v_seance.element_des as element, v_seance.ens_nom as nom, v_seance.ens_prenom as prenom,TIME_FORMAT(xseance.Heure_Debut, '%H:%i') as Heure_Debut,
   TIME_FORMAT(xseance.Heure_Fin, '%H:%i') as Heure_Fin,xseance.Date_Séance,xseance.Année_Lib,xseance.Groupe 
   FROM xseance INNER JOIN v_seance ON v_seance.code_seance=xseance.id_séance WHERE xseance.id_séance=$seance";
  
    $sean =  ApiController::execute($m,$this->em);

    /// etud CAT A

    $etud= "SELECT xseance_absences.ID_Admission as  id_admission,xseance_absences.Nom,xseance_absences.Prénom,TIME_FORMAT(xseance_absences.Heure_Pointage, '%H:%i') AS Heure_Pointage,xseance_absences.Categorie
    FROM xseance_absences
    
    WHERE xseance_absences.ID_Séance=$seance and xseance_absences.Categorie='A'";
   
    $etpdf =  ApiController::execute($etud,$this->em);


  /// etud Cat B

    $etudb= "SELECT xseance_absences.ID_Admission as  id_admission,xseance_absences.Nom,xseance_absences.Prénom,TIME_FORMAT(xseance_absences.Heure_Pointage, '%H:%i') AS Heure_Pointage,xseance_absences.Categorie
    FROM xseance_absences
    
    WHERE xseance_absences.ID_Séance=$seance and xseance_absences.Categorie='B'";

    $etbpdf =  ApiController::execute($etudb,$this->em);
    /// etud Cat C

    $etudc= "SELECT xseance_absences.ID_Admission as  id_admission,xseance_absences.Nom,xseance_absences.Prénom,TIME_FORMAT(xseance_absences.Heure_Pointage, '%H:%i') AS Heure_Pointage,xseance_absences.Categorie
    FROM xseance_absences
    
    WHERE xseance_absences.ID_Séance=$seance  AND xseance_absences.Categorie='C'";
   
    $etcpdf =  ApiController::execute($etudc,$this->em);



    // etud Cat D


    $etudd= "SELECT xseance_absences.ID_Admission as  id_admission,xseance_absences.Nom,xseance_absences.Prénom,TIME_FORMAT(xseance_absences.Heure_Pointage, '%H:%i') AS Heure_Pointage,xseance_absences.Categorie
    FROM xseance_absences
    
    WHERE xseance_absences.ID_Séance=$seance and xseance_absences.Categorie='D'";
   
    $etcdpdf =  ApiController::execute($etudd,$this->em);



    // count A

    $A= "SELECT COUNT(xseance_absences.Categorie) AS A FROM xseance_absences WHERE xseance_absences.Categorie='A' AND ID_Séance=$seance";
 
    $A =  ApiController::execute($A,$this->em);





    // count B

    $B= "SELECT COUNT(xseance_absences.Categorie) AS B FROM xseance_absences WHERE xseance_absences.Categorie='B' AND ID_Séance=$seance";
   
    $B =  ApiController::execute($B,$this->em);


     // count C

     $C= "SELECT COUNT(xseance_absences.Categorie) AS C FROM xseance_absences WHERE xseance_absences.Categorie='C' AND ID_Séance=$seance";
  
    $C =  ApiController::execute($C,$this->em);



      // count D

    $D= "SELECT COUNT(xseance_absences.Categorie) AS D FROM xseance_absences WHERE xseance_absences.Categorie='D' AND ID_Séance=$seance";
 
    $D =  ApiController::execute($D,$this->em);


    $T= "SELECT COUNT(xseance_absences.Categorie) AS T FROM xseance_absences WHERE (xseance_absences.Categorie='D' or xseance_absences.Categorie='A' or xseance_absences.Categorie='B'
    or xseance_absences.Categorie='C') AND ID_Séance=$seance";
 
    $T =  ApiController::execute($T,$this->em);

   
    $html = $this->render('assiduite/pdf/feuil.html.twig' , [
        "sean" => $sean,
        "etud" =>$etpdf,
        "etudB" =>$etbpdf,
        "etudC" =>$etcpdf,
        "etudBD" =>$etcdpdf,
        "A" => $A,
        "B" => $B,
        "C" => $C,
        "D" => $D,
        "date" =>$date2,
        "T" =>$T
        
    ])->getContent();

    
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'margin_top' => 75,
    ]);            
    $mpdf->SetTitle('Feuil');
    $mpdf->SetHTMLHeader(
        $this->render("assiduite/pdf/header.html.twig"
        , [
            "sean" => $sean,
            "etud" =>$etpdf,
            "etudB" =>$etbpdf,
            "etudC" =>$etcpdf,
            "etudBD" =>$etcdpdf,
            "A" => $A,
            "B" => $B,
            "C" => $C,
            "D" => $D,
            "date" =>$date2,
            "T" =>$T
            
        ])->getContent()
    );








    $mpdf->SetHTMLFooter(
        $this->render("assiduite/pdf/footer.html.twig"
        , [
            "sean" => $sean,
            "etud" =>$etpdf,
            "etudB" =>$etbpdf,
            "etudC" =>$etcpdf,
            "etudBD" =>$etcdpdf,
            "A" => $A,
            "B" => $B,
            "C" => $C,
            "D" => $D,
            "date" =>$date2,
            "T" =>$T
            
        ])->getContent()
    );
    $mpdf->WriteHTML($html);
    $mpdf->Output('fueil' , 'I');
    

}

    
#[Route('/pdfsynthese/{datetoday}', name: 'pdfsynthese')]
public function pdfsynthese($datetoday)
{

    ini_set("pcre.backtrack_limit", "5000000");

$TodayDate= new \DateTime();
$date= date_format($TodayDate, 'Y-m-d');
setlocale(LC_TIME, "fr_FR", "French");
$date2 = strftime("%A %d %B %G", strtotime($date)); 
   //seance+date+prof
 $requete_promo_count = "SELECT COUNT(x_inscription_grp.code_admission) AS etud,x_inscription_grp.promotion_code 
                         FROM x_inscription_grp GROUP BY promotion_code";
 $count_global =  ApiController::execute($requete_promo_count,$this->em);

 $requete_promo_daily = "SELECT COUNT(xseance_absences.ID_Admission) as etud,xseance.ID_Promotion as promotion_code FROM xseance_absences 
                         INNER JOIN xseance ON xseance.ID_Séance=xseance_absences.ID_Séance 
                         WHERE  xseance.Date_Séance='2022-09-20' GROUP by xseance.ID_Promotion
";
 $count_global_jr =  ApiController::execute($requete_promo_daily,$this->em);

 $requete_promo_abs = "SELECT COUNT(xseance.ID_Séance) as etud,xseance.ID_Promotion as promotion_code FROM xseance 
                         INNER JOIN xseance_absences ON xseance.ID_Séance=xseance_absences.ID_Séance
                        WHERE xseance.Date_Séance='2022-09-20' AND (xseance_absences.Categorie='D' OR xseance_absences.Categorie='C'
                         OR xseance_absences.Categorie_Enseig='AD' OR xseance_absences.Categorie_Enseig='BD') GROUP BY xseance.ID_Promotion";
 $count_global_abs =  ApiController::execute($requete_promo_abs,$this->em);

 $date1 = date('Y-m-d'); // Date du jour
 setlocale(LC_TIME, "fr_FR", "French");
 $date2 = strftime("%A %d %B %G", strtotime($date1)); 
 $date3 = strftime("%A %d %B %G", strtotime($date)); 

   
    $html = $this->render('assiduite/pdf/synthese.html.twig' , [
      
        "count_global" => $count_global,
        "count_global_jr" => $count_global_jr,
        "count_global_abs" => $count_global_abs,
        "date" =>$date2,
        "dat" =>$date3
    ])->getContent();

    
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'margin_top' => 75,
    ]);            
    $mpdf->SetTitle('Feuil');
    $mpdf->SetHTMLHeader(
        $this->render("assiduite/pdf/header_synthese.html.twig"
        , [
          
           
            
        ])->getContent()
    );
    $mpdf->SetHTMLFooter(
        $this->render("assiduite/pdf/footer_synthese.html.twig"
        , [
            "date" =>$date2,
            "dat" =>$date3
            
        ])->getContent()
    );
    $mpdf->WriteHTML($html);
    $mpdf->Output('fueil' , 'I');





}

   
}
