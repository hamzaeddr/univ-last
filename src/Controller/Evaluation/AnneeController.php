<?php

namespace App\Controller\Evaluation;

use App\Controller\ApiController;
use App\Entity\AcAnnee;
use App\Entity\AcEtablissement;
use App\Entity\AcModule;
use App\Entity\AcPromotion;
use App\Entity\AcSemestre;
use App\Entity\ExAnotes;
use App\Entity\ExControle;
use App\Entity\ExMnotes;
use App\Entity\ExSnotes;
use App\Entity\PeStatut;
use App\Entity\TInscription;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evaluation/annee')]
class AnneeController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'evaluation_annee')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'evaluation_annee', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etablissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);

        return $this->render('evaluation/annee/index.html.twig', [
            'operations' => $operations,
            'etablissements' => $etablissements,
        ]);
    }
    #[Route('/list/{promotion}', name: 'evaluation_annee_list')]
    public function evaluationAnneeList(Request $request, AcPromotion $promotion): Response
    {
        $order = $request->get('order');
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $verify = $this->em->getRepository(ExControle::class)->alreadyValidateAnnee($promotion, $annee);
        $check = 0; //valider cette opération
        if(!$verify){
            $check = 1; //opération déja validé
        }
        
        $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromo($annee, $promotion, $order);
        $data_saved = [];
        // dd('amine');
        $modules = $this->em->getRepository(AcModule::class)->findByPromotion($promotion, $annee);
        foreach ($inscriptions as $inscription) {
            $moyenne = 0;
            $moyenne_normal = 0;
            $total_coef = 0;
            $total_coef_normal = 0;
            $noteModules = [];
            foreach ($modules as $module) {

                $total_coef += $module->getCoefficient();
                $mnote = $this->em->getRepository(ExMnotes::class)->findOneBy(['module' => $module, 'inscription' => $inscription]);
                if(!$mnote){
                    dd($module);
                }
                $moyenne += $mnote->getNote() * $module->getCoefficient();
                
                if ($module->getType() == 'N') {
                    $moyenne_normal += $mnote->getNote() * $module->getCoefficient();
                    $total_coef_normal += $module->getCoefficient();
                }

                array_push($noteModules, [
                    'note' => $mnote->getNote(),
                    'module' => $module,
                    'statut' => $this->getStatutModule($inscription, $module)
                ]);

            }
            $moyenne = number_format($moyenne / $total_coef, 2, '.', ' ');
            $moyenneNormal = number_format($moyenne_normal / $total_coef_normal, 2, '.', ' ');
            
            array_push($data_saved, [
                'inscription' => $inscription,
                'noteModules' => $noteModules,
                'moyenneNormal' =>$moyenneNormal, 
                'moyenneSec' => $moyenne
            ]);
        }
        // dd($data_saved);
        if($order == 3) {
            $moyenne = array_column($data_saved, 'moyenneNormal');
            array_multisort($moyenne, SORT_DESC, $data_saved);
        } else if($order == 4){
            $moyenne = array_column($data_saved, 'moyenneNormal');
            array_multisort($moyenne, SORT_ASC, $data_saved);
        }
        $session = $request->getSession();
        $session->set('data_annee', [
            'data_saved' => $data_saved, 
            'modules' => $modules,
            'promotion' => $promotion
        ]);
        $html = $this->render('evaluation/annee/pages/list_epreuve_normal.html.twig', [
            'data_saved' => $data_saved,
            'modules' => $modules
        ])->getContent();
        // dd($html);
        return new JsonResponse(['html' => $html, 'check' => $check]);
    } 
    
    #[Route('/impression/{type}/{affichage}', name: 'evaluation_annee_impression')]
    public function evaluationAnneeImpression(Request $request, $type, $affichage) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_annee')['data_saved'];
        $modules = $session->get('data_annee')['modules'];
        $promotion = $session->get('data_annee')['promotion'];
        $semestres = $this->em->getRepository(AcSemestre::class)->findBy(['promotion' => $promotion, 'active' => 1]);
        $semstre1 = $semestres[0];
        $semstre2 = $semestres[1];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $infos =  [
            'dataSaved' => $dataSaved,
            'modules' => $modules,
            'affichage' => $affichage,
            'statutModules' => $this->em->getRepository(PeStatut::class)->findBy(['type' => 'M']),
            'statutSemestres' => $this->em->getRepository(PeStatut::class)->findBy(['type' => 'S']),
            'statutAnnees' => $this->em->getRepository(PeStatut::class)->findBy(['type' => 'A']),
            'etablissement' => $annee->getFormation()->getEtablissement(),
            'semestre1' => $semstre1,
            'semestre2' => $semstre2
        ];
        if($type == "normal"){
            $html = $this->render("evaluation/annee/pdfs/normal.html.twig", $infos)->getContent();
        } else if ($type == "anonymat") {
            $html = $this->render("evaluation/annee/pdfs/anonymat.html.twig", $infos)->getContent();
        }
        else if ($type == "clair") {
            $html = $this->render("evaluation/annee/pdfs/clair.html.twig", $infos)->getContent();
        }
        else if ($type == "rat") {
            foreach($dataSaved as $key => $value) {
                if($value['moyenneSec'] >= 10) {  
                  unset($dataSaved[$key]);
                }
            }
            // dd($inscriptionsArray);
            $infos['dataSaved'] = $dataSaved;
            $html = $this->render("evaluation/annee/pdfs/rattrapage.html.twig", $infos)->getContent();
        } else {
            die("403 something wrong !");
        }
        // dd($html);
        
        $html .= $this->render("evaluation/annee/pdfs/footer.html.twig")->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '5',
            'margin_right' => '5',
            'margin_top' => '35',
            'margin_bottom' => '10',
            'format' => 'A4-L',
            'margin_header' => '2',
            'margin_footer' => '2'
            ]);
        $mpdf->SetHTMLHeader($this->render("evaluation/annee/pdfs/header.html.twig", [
            'annee' => $annee,
            'promotion' => $promotion,
            'affichage' => $affichage
        ])->getContent());
        $mpdf->defaultfooterline = 0;
        $mpdf->SetFooter('Page {PAGENO} / {nb}');
        $mpdf->WriteHTML($html);
        $mpdf->Output("annee_deliberation_".$promotion->getId().".pdf", "I");
    }

    public function getStatut($inscription, $statut)
    {
        return new Response($this->em->getRepository(ExAnotes::class)->getStatutByColumn($inscription, $statut), 200, ['Content-Type' => 'text/html']);
    }
    public function getStatutModule($inscription, $module)
    {
        return $this->em->getRepository(ExMnotes::class)->getStatutAffDef($inscription, $module);
    }
    public function getNoteSemestre($inscription, $semestre, $statut)
    {
        return new Response($this->em->getRepository(ExSnotes::class)->getStatutAffDef($inscription, $semestre, $statut), 200, ['Content-Type' => 'text/html']);
    }
    #[Route('/enregistre', name: 'evaluation_annee_enregistre')]
    public function evaluationAnneeEnregistre(Request $request) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_annee')['data_saved'];
        $promotion = $this->em->getRepository(AcPromotion::class)->find($session->get('data_annee')['promotion']->getId());
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $exControle = $this->em->getRepository(ExControle::class)->alreadyValidateAnnee($promotion, $annee);
        $verify = $this->em->getRepository(ExControle::class)->checkIfyoucanCalculAnnee($annee, $promotion);
        if(!$exControle) {
            return new JsonResponse("Année deja valide", 500);
        }
        if(!$verify){
            return new JsonResponse("Operation déja valider", 500);
        }

        foreach ($dataSaved as $data) {
            $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
            $inscriptionAnnee  = $this->em->getRepository(ExAnotes::class)->findOneBy(['inscription' => $inscription]);
            if(!$inscriptionAnnee) {
                $inscriptionAnnee = new ExAnotes();
                $inscriptionAnnee->setInscription($inscription);
                $inscriptionAnnee->setAnnee($annee);
                $inscriptionAnnee->setUser($this->getUser());
                $inscriptionAnnee->setCreated(new \DateTime("now"));
                $this->em->persist($inscriptionAnnee);
                $this->em->flush();
            }
            $inscriptionAnnee->setNote(
                $data['moyenneNormal']
            );
            $inscriptionAnnee->setNoteSec(
                $data['moyenneSec']
            );
            
            
            $this->em->flush();

        }      
        
        return new JsonResponse("Bien Enregistre", 200);
    }
    #[Route('/valider', name: 'evaluation_annee_valider')]
    public function evaluationAnneeValider(Request $request) 
    {         
        $session = $request->getSession();
        $promotion = $session->get('data_annee')['promotion'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $exControle = $this->em->getRepository(ExControle::class)->canValidateAnnee($promotion, $annee);
        // dd($exControle);
        if($exControle) {
            return new JsonResponse("Veuillez Valider Toutes les semestres pour valider cet annee ", 500);
        }
        $this->em->getRepository(ExControle::class)->updateAnneeByElement($promotion, $annee, 1);

        return new JsonResponse("Bien Valider", 200);
    }
    #[Route('/devalider', name: 'evaluation_annee_devalider')]
    public function evaluationAnneeDevalider(Request $request) 
    {         
        $session = $request->getSession();
        $session = $request->getSession();
        $promotion = $session->get('data_annee')['promotion'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $this->em->getRepository(ExControle::class)->updateAnneeByElement($promotion, $annee, 0);
        $this->em->flush();

        return new JsonResponse("Bien Devalider", 200);
    }
    #[Route('/recalculer', name: 'evaluation_annee_recalculer')]
    public function evaluationAnneetrecalculer(Request $request) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_annee')['data_saved'];
        foreach ($dataSaved as $data) {
            $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
            $inscriptionAnnee  = $this->em->getRepository(ExSnotes::class)->findOneBy(['inscription' => $inscription]);
            $inscriptionAnnee->setNote(
                $data['moyenneNormal']
            );
            $inscriptionAnnee->setNoteSec(
                $data['moyenneSec']
            );
        }
        $this->em->flush();
        return new JsonResponse("Bien Recalculer", 200);

    }
    #[Route('/statut/{type}', name: 'administration_annee_statut')]
    public function administrationAnneStatut(Request $request, $type) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_annee')['data_saved'];
        $modules = $session->get('data_annee')['modules'];
        $promotion = $session->get('data_annee')['promotion'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        
        if($type == "apresrachat") {
            foreach ($dataSaved as $data) {
                $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
                $anote = $this->em->getRepository(ExAnotes::class)->findOneBy(['inscription' => $inscription]);                
                
                $data_semestre_min = $this->em->getRepository(ExSnotes::class)->GetSemestreByCodeAnneeCodePromotion($annee, $promotion, $inscription, 'min', 'statutDef');
                $data_semestre_min_aff = $this->em->getRepository(ExSnotes::class)->GetSemestreByCodeAnneeCodePromotion($annee, $promotion, $inscription, 'min', 'statutAff');
                                
                $data_semestre_max = $this->em->getRepository(ExSnotes::class)->GetSemestreByCodeAnneeCodePromotion($annee, $promotion, $inscription, 'max', 'statutDef');
                $data_semestre_max_aff = $this->em->getRepository(ExSnotes::class)->GetSemestreByCodeAnneeCodePromotion($annee, $promotion, $inscription, 'max', 'statutAff');
              
                $nbr_modules_statut_aff=$this->em->getRepository(ExMnotes::class)->GetNbrModuleByInscription($annee, $inscription, 10);
                 if ($nbr_modules_statut_aff) {
                    $nbr_modules = $nbr_modules_statut_aff[0]['nbr_modules'];
                }
                 $min_semestre_statut_def = $max_semestre_statut_def = "";
                if ($data_semestre_min) {
                    $min_semestre_statut_def = $data_semestre_min[0]->getStatutDef()->getId();
                    $semestre_statut_aff = $data_semestre_min_aff[0]->getStatutAff()->getId();
                }if ($data_semestre_max) {
                    $max_semestre_statut_def = $data_semestre_max[0]->getStatutDef()->getId();
                    $max_semestre_statut_aff = $data_semestre_max_aff[0]->getStatutAff()->getId();
                }
                $result = $this->AnneeGetStatutApresRachat($min_semestre_statut_def, $max_semestre_statut_def , $semestre_statut_aff,$max_semestre_statut_aff,$nbr_modules);
                if (isset($result) and !empty($result)) {
                    $anote->setStatutS2(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_s2'])
                    );
                    $anote->setStatutAff(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_aff'])
                    );
                    $anote->setStatutDef(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_def'])
                    );
                }
            }
        }  
        elseif ($type == 'statutanneecategorie') {
            foreach ($dataSaved as $data) {
                $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
                $anote = $this->em->getRepository(ExAnotes::class)->findOneBy(['inscription' => $inscription]);                
                $data_semestre = $this->em->getRepository(ExSnotes::class)->GetCategorieSemestreByCodeAnnee($annee, $inscription);
                
                $result = $this->AnneeGetStatutCategories($data_semestre[0]->getCategorie() ,$data_semestre[1]->getCategorie(),$anote->getStatutAff()->getId(),$data_semestre[0]->getStatutAff()->getId(),$data_semestre[1]->getStatutAff()->getId());
                if (isset($result) and !empty($result)) {
                    $anote->setCategorie($result);
                }
            }
        }
        
        $this->em->flush();
        return new JsonResponse("Bien enregistre", 200);

    }
    public function AnneeGetStatutApresRachat($min_semestre_statut_def, $max_semestre_statut_def, $semestre_statut_aff, $max_semestre_statut_aff, $nbr_modules) {

        $send_data = array();
        if ($nbr_modules > 2) {
            $send_data['statut_s2'] = 44;
            $send_data['statut_def'] = 44;
            $send_data['statut_aff'] = 44;
        } else {
            switch ($max_semestre_statut_aff) {
                case 71:
                    switch ($semestre_statut_aff) {
                        case 37:
                            $send_data['statut_aff'] = 42;
                            break;
                        case 38:
                            $send_data['statut_aff'] = 43;
                            break;
                        case 39:
                            $send_data['statut_aff'] = 44;
                            break;
                        case 36:
                            $send_data['statut_aff'] = 70;
                            break; 
                        case 56:
                            $send_data['statut_aff'] = 70;
                            break;
                    }
                    break;
                case 57:
                    $send_data['statut_aff'] = 44;
                    break;
                case 39:
                    $send_data['statut_aff'] = 44;
                    break;
                case 38:
                    $send_data['statut_aff'] = 43;
                    break;
                case 37:
                    $send_data['statut_aff'] = 42;
                    break;
                case 36:
                    $send_data['statut_aff'] = 41;
                    break;
                case 56:
                    switch ($semestre_statut_aff) {
                        case 37:
                            $send_data['statut_aff'] = 42;
                            break;
                        case 38:
                            $send_data['statut_aff'] = 43;
                            break;
                        case 39:
                            $send_data['statut_aff'] = 44;
                            break;
                        case 36:
                            $send_data['statut_aff'] = 70;
                            break; 
                        case 56:
                            $send_data['statut_aff'] = 70;
                            break;
                    }
                    break;
            }
            switch ($max_semestre_statut_def) {
                case 71:
                    switch ($min_semestre_statut_def) {
                        case 37:
                            $send_data['statut_s2'] = 42;
                            $send_data['statut_def'] = 42;
                            break;
                        case 38:
                            $send_data['statut_s2'] = 43;
                            $send_data['statut_def'] = 43;
                            break;
                        case 39:
                            $send_data['statut_s2'] = 44;
                            $send_data['statut_def'] = 44;
                            break;
                        case 36:
                            $send_data['statut_s2'] = 70;
                            $send_data['statut_def'] = 70;
                            break;
                        case 56:
                            $send_data['statut_s2'] = 70;
                            $send_data['statut_def'] = 70;
                            break;
                    }
                    break;
                case 57:
                    $send_data['statut_s2'] = 44;
                    $send_data['statut_def'] = 44;
                    break;
                case 39:
                    $send_data['statut_s2'] = 44;
                    $send_data['statut_def'] = 44;
                    break;
                case 38:
                    $send_data['statut_s2'] = 43;
                    $send_data['statut_def'] = 43;
                    break;
                case 37:
                    $send_data['statut_s2'] = 42;
                    $send_data['statut_def'] = 42;
                    break;
                case 36:
                    $send_data['statut_s2'] = 41;
                    $send_data['statut_def'] = 41;
                    break;
                case 56:
                    switch ($min_semestre_statut_def) {
                        case 37:
                            $send_data['statut_s2'] = 42;
                            $send_data['statut_def'] = 42;
                            break;
                        case 38:
                            $send_data['statut_s2'] = 43;
                            $send_data['statut_def'] = 43;
                            break;
                        case 39:
                            $send_data['statut_s2'] = 44;
                            $send_data['statut_def'] = 44;
                            break;
                        case 36:
                            $send_data['statut_s2'] = 70;
                            $send_data['statut_def'] = 70;
                            break;
                        case 56:
                            $send_data['statut_s2'] = 70;
                            $send_data['statut_def'] = 70;
                            break;
                    }
            }
            if ($min_semestre_statut_def == 40) {
                $send_data['statut_s2'] = 46;
                $send_data['statut_def'] = 46;
                $send_data['statut_aff'] = 46;
            }
        }

        return $send_data;
    }
    public function AnneeGetStatutCategories($categ_semestre_1, $categ_semestre_2, $statut_annee, $statut_semestre_1, $statut_semestre_2) {
        $categorie = null;
        
        switch ($statut_annee) {
            case 41 : $categorie = 'A';
                      break;
            case 70 : $categorie = 'B';
                break;
            case 42 : $categorie = 'C';
                break;
            case 43 : $categorie = 'CR';
                break;
            case 46 : $categorie = 'D';
                break;
            case 44 : 
                if ($statut_semestre_1 == 57 ){
                    $categorie = 'E';
                }
                else{
                    if ($statut_semestre_1 == 39){
                        $categorie = 'F';
                    }
                    else{                     
                            $categorie = $categ_semestre_2;
                    }   
                }
                break;
        }
//        echo 'categ f : '.$categorie;
        
        return $categorie;
    }
}
