<?php

namespace App\Controller\Evaluation;

use Mpdf\Mpdf;
use App\Entity\AcAnnee;
use App\Entity\AcModule;
use App\Entity\ExEnotes;
use App\Entity\AcElement;
use App\Entity\ExControle;
use App\Entity\TInscription;
use App\Entity\AcEtablissement;
use App\Controller\ApiController;
use App\Entity\ExMnotes;
use App\Entity\PeStatut;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/evaluation/module')]
class ModuleController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'evaluation_module')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'evaluation_module', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etablissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);

        return $this->render('evaluation/module/index.html.twig', [
            'operations' => $operations,
            'etablissements' => $etablissements,
        ]);
    }
    #[Route('/list/{module}', name: 'evaluation_module_list')]
    public function evaluationElementList(Request $request, AcModule $module): Response
    {
        $order = $request->get('order');
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($module->getSemestre()->getPromotion()->getFormation());
        $verify = $this->em->getRepository(ExControle::class)->checkIfyoucanCalculModule($annee, $module);
        // dd($verify);
        $check = 0; //valider cette opération
        if(!$verify){
            $check = 1; //opération déja validé
        }
        // $validated = $this->em->getRepository(ExControle::class)->checkIfAllElementValide($annee, $module);
        // if($validated) {
        //     return new JsonResponse("Veuillez valider tout les elements pour calucler", 500);
        // }
        $promotion = $module->getSemestre()->getPromotion();
        $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromo($annee, $promotion, $order);
        $data_saved = [];
        // dd($inscriptions);
        $elements = $this->em->getRepository(AcElement::class)->findBy(['module' => $module, 'active' => 1]);
        foreach ($inscriptions as $inscription) {
            $moyenne_ini = 0;
            $moyenne_rat = 0;
            $moyenne_tot = 0;
            $note_rachat = 0;
            $total_coef = 0;
            $nb_ini = 0;
            $nb_rat = 0;
            $nb_rachat = 0;
            $nb_tot = 0;
            $nb_ele = 0;
            $nt_rach = 0;
            $moy_rat = 0;
            $noteElements = [];
            foreach ($elements as $element) {
                $total_coef += $element->getCoefficient();
                $enote = $this->em->getRepository(ExEnotes::class)->findOneBy(['element' => $element, 'inscription' => $inscription]);
                
                // dd($element, $inscription);
                if(!$enote->getNoteIni()){
                    $nb_ini++; 
                }
                else{
                    $moyenne_ini += $enote->getNoteIni() * $element->getCoefficient();
                }
                if(!$enote->getNoteRat()){
                    $nb_rat++;           
                    $moyenne_rat += $enote->getNoteIni() * $element->getCoefficient();
                }
                else{
                    $moyenne_rat += $enote->getNoteRat() * $element->getCoefficient();  
                }
                if(!$enote->getNote()){
                    $nb_tot++;
                }
                else{
                    $moyenne_tot += $enote->getNote() * $element->getCoefficient();
                }
                
                if(!$enote->getNoteRachat()){
                    $nb_rachat++;
                }
                else{
                    $note_rachat +=$enote->getNoteRachat()* $element->getCoefficient();
                }
                $nb_ele++;
                array_push($noteElements, $enote->getNote());

            }
            if($nb_ele == $nb_ini){
                $moyenne_ini = "-1";
            }
            if($nb_ele == $nb_rat){
                $moyenne_rat = "-1";
            }
            if($nb_ele == $nb_rachat){
                $note_rachat = "-1";
            }
            if($nb_ele == $nb_tot){
                $moyenne_tot = "-1";
            }
            $moy_ini = number_format($moyenne_ini / $total_coef, 2, '.', ' ') ; 
            $moy_rat = number_format($moyenne_rat / $total_coef, 2, '.', ' ') ; 
            $nt_rach = number_format($note_rachat / $total_coef, 2, '.', ' ') ; 
            $moy_tot = number_format($moyenne_tot / $total_coef, 2, '.', ' ') ; 

            array_push($data_saved, [
                'inscription' => $inscription,
                'noteElements' => $noteElements,
                'moyenneIni' => $moy_ini, 
                'moyenneRat' => $moy_rat, 
                'noteRachat' =>$nt_rach, 
                'moyenneTot' => $moy_tot
            ]);
        }
        // dd($data_saved);
        if($order == 3) {
            $moyenne = array_column($data_saved, 'moyenneTot');
            array_multisort($moyenne, SORT_DESC, $data_saved);
        } else if($order == 4){
            $moyenne = array_column($data_saved, 'moyenneTot');
            array_multisort($moyenne, SORT_ASC, $data_saved);
        }
        $session = $request->getSession();
        $session->set('data_module', [
            'data_saved' => $data_saved, 
            'module' => $module,
            'elements' => $elements
        ]);
        $html = $this->render('evaluation/module/pages/list_epreuve_normal.html.twig', [
            'data_saved' => $data_saved,
            'elements' => $elements
        ])->getContent();
        // dd($html);
        return new JsonResponse(['html' => $html, 'check' => $check]);
    } 
    #[Route('/impression/{type}/{affichage}', name: 'evaluation_module_impression')]
    public function evaluationModuleImpression(Request $request, $type, $affichage) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_module')['data_saved'];
        $module = $session->get('data_module')['module'];
        $elements = $session->get('data_module')['elements'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($module->getSemestre()->getPromotion()->getFormation());
        $infos =  [
            'dataSaved' => $dataSaved,
            'module' => $module,
            'elements' => $elements,
            'affichage' => $affichage,
            'statuts' => $this->em->getRepository(PeStatut::class)->findBy(['type' => 'M']),
            'etablissement' => $annee->getFormation()->getEtablissement(),
        ];
        if($type == "normal"){
            $html = $this->render("evaluation/module/pdfs/normal.html.twig", $infos)->getContent();
        } else if ($type == "anonymat") {
            $html = $this->render("evaluation/module/pdfs/anonymat.html.twig", $infos)->getContent();
        }
        else if ($type == "clair") {
            $html = $this->render("evaluation/module/pdfs/clair.html.twig", $infos)->getContent();
        }
        else if ($type == "rat") {
            foreach($dataSaved as $key => $value) {
                if($value['moyenneTot'] >= 10) {  
                  unset($dataSaved[$key]);
                }
            }
            // dd($inscriptionsArray);
            $infos['dataSaved'] = $dataSaved;
            $html = $this->render("evaluation/module/pdfs/rattrapage.html.twig", $infos)->getContent();
        } else {
            die("403 something wrong !");
        }
        $html .= $this->render("evaluation/module/pdfs/footer.html.twig")->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '5',
            'margin_right' => '5',
            'margin_top' => '35',
            'margin_bottom' => '5',
            'format' => 'A4-L',
            'margin_header' => '2',
            'margin_footer' => '2'
            ]);
        $mpdf->SetHTMLHeader($this->render("evaluation/module/pdfs/header.html.twig", [
            'module' => $module,
            'annee' => $annee,
            'affichage' => $affichage
        ])->getContent());
        $mpdf->defaultfooterline = 0;
        $mpdf->SetFooter('Page {PAGENO} / {nb}');
        $mpdf->WriteHTML($html);
        $mpdf->Output("module_deliberation_".$module->getId().".pdf", "I");
    }

    public function getStatut($inscription, $module, $statut)
    {
        return new Response($this->em->getRepository(ExMnotes::class)->getStatutByColumn($inscription, $module, $statut), 200, ['Content-Type' => 'text/html']);
    }

    #[Route('/valider', name: 'evaluation_module_valider')]
    public function evaluationModuleValider(Request $request) 
    {         
        $session = $request->getSession();
        $module = $session->get('data_module')['module'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($module->getSemestre()->getPromotion()->getFormation());
        $exControle = $this->em->getRepository(ExControle::class)->canValidateModule($module, $annee);
        if($exControle) {
            return new JsonResponse("Veuillez Valider Toutes les elements pour valider ce module ", 500);
        }
        $this->em->getRepository(ExControle::class)->updateModuleByElement($module, $annee, 1);

        return new JsonResponse("Bien Valider", 200);
    }
    #[Route('/devalider', name: 'evaluation_module_devalider')]
    public function evaluationModuleDealider(Request $request) 
    {         
        $session = $request->getSession();
        $module = $session->get('data_module')['module'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($module->getSemestre()->getPromotion()->getFormation());
        $this->em->getRepository(ExControle::class)->updateModuleByElement($module, $annee, 0);
        $this->em->flush();

        return new JsonResponse("Bien Devalider", 200);
    }

    #[Route('/enregistre', name: 'evaluation_module_enregistre')]
    public function evaluationModuleEnregistre(Request $request) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_module')['data_saved'];
        $module = $this->em->getRepository(AcModule::class)->find($session->get('data_module')['module']->getId());
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($module->getSemestre()->getPromotion()->getFormation());
        $exControle = $this->em->getRepository(ExControle::class)->alreadyValidateModule($module, $annee);
        $verify = $this->em->getRepository(ExControle::class)->checkIfyoucanCalculModule($annee, $module);
        if(!$exControle) {
            return new JsonResponse("Module deja valide", 500);
        }
        if(!$verify){
            return new JsonResponse("Operation déja valider", 500);
        }

        foreach ($dataSaved as $data) {
            $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
            $inscriptionModule  = $this->em->getRepository(ExMnotes::class)->findOneBy(['inscription' => $inscription, 'module' => $module]);
            if(!$inscriptionModule) {
                $inscriptionModule = new ExMnotes();
                $inscriptionModule->setInscription($inscription);
                $inscriptionModule->setModule($module);
                $inscriptionModule->setUser($this->getUser());
                $inscriptionModule->setCreated(new \DateTime("now"));
                $this->em->persist($inscriptionModule);
                $this->em->flush();
            }
            $inscriptionModule->setNoteIni(
                $data['moyenneIni'] < 0 ? null : $data['moyenneIni']
            );
            $inscriptionModule->setNoteRat(
                $data['moyenneRat'] < 0 ? null : $data['moyenneRat']
            );
            $inscriptionModule->setNoteRachat(
                $data['noteRachat'] < 0 ? null : $data['noteRachat']
            );
            $inscriptionModule->setNote(
                $data['moyenneTot'] < 0 ? null : $data['moyenneTot']
            );

            $this->em->flush();

        }      
        
        return new JsonResponse("Bien Enregistre", 200);
    }
    #[Route('/recalculer', name: 'evaluation_module_recalculer')]
    public function evaluationModuleRecalculer(Request $request) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_module')['data_saved'];
        $module = $this->em->getRepository(AcModule::class)->find($session->get('data_module')['module']->getId());
        foreach ($dataSaved as $data) {
            $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
            $inscriptionModule  = $this->em->getRepository(ExMnotes::class)->findOneBy(['inscription' => $inscription, 'module' => $module]);
            $inscriptionModule->setNoteIni(
                $data['moyenneIni'] < 0 ? null : $data['moyenneIni']
            );
            $inscriptionModule->setNoteRat(
                $data['moyenneRat'] < 0 ? null : $data['moyenneRat']
            );
            $inscriptionModule->setNoteRachat(
                $data['noteRachat'] < 0 ? null : $data['noteRachat']
            );
            $inscriptionModule->setNote(
                $data['moyenneTot'] < 0 ? null : $data['moyenneTot']
            );
        }
        $this->em->flush();
        return new JsonResponse("Bien Recalculer", 200);

    }
    #[Route('/statut/{type}', name: 'administration_module_statut')]
    public function administrationElementStatut(Request $request, $type) 
    {         
        $session = $request->getSession();
        $dataSaved = $session->get('data_module')['data_saved'];
        $elements = $session->get('data_module')['elements'];
        $module = $session->get('data_module')['module'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($module->getSemestre()->getPromotion()->getFormation());
        if($type == 'avantrachat'){
            foreach ($dataSaved as $data) {
                $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
                $mnote = $this->em->getRepository(ExMnotes::class)->findOneBy(['module' => $module, 'inscription' => $inscription]);
                $data_elements_min = $this->em->getRepository(ExEnotes::class)->GetElementsByCodeAnneeCodeModule($annee, $module, $inscription, 'min', 'statutDef');
                $data_elements_max = $this->em->getRepository(ExEnotes::class)->GetElementsByCodeAnneeCodeModule($annee, $module, $inscription, 'max', 'statutDef');
                $data_elements_max_aff = $this->em->getRepository(ExEnotes::class)->GetElementsByCodeAnneeCodeModule($annee, $module, $inscription, 'max', 'statutAff');
                $min_element_module_statut_def = $max_element_module_statut_def = "";
                // dd($data_elements_min);
                if ($data_elements_min) {
                    $min_element_module_statut_def = $data_elements_min[0]->getStatutDef()->getId();
                }
                if ($data_elements_max) {
                    $max_element_module_statut_def = $data_elements_max[0]->getStatutDef()->getId();
                    $max_element_module_statut_aff = $data_elements_max_aff[0]->getStatutAff()->getId();
                }
                $result = $this->ModuleGetStatutAvantRachat($mnote, 8, 10, $min_element_module_statut_def, $max_element_module_statut_def, $max_element_module_statut_aff);

                if (isset($result) and !empty($result)) {
                    $mnote->setStatutS2(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_s2'])
                    );
                    $mnote->setStatutAff(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_aff'])
                    );
                    $mnote->setStatutDef(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_def'])
                    );
                }
            }
        
        }
        elseif($type == "apresrachat") {
            foreach ($dataSaved as $data) {
                $inscription = $this->em->getRepository(TInscription::class)->find($data['inscription']->getId());
                $mnote = $this->em->getRepository(ExMnotes::class)->findOneBy(['module' => $module, 'inscription' => $inscription]);
                
                $data_elements = $this->em->getRepository(ExEnotes::class)->GetElementsByCodeAnneeCodeModule($annee, $module, $inscription, 'all', 'statutDef');
                
                $result = $this->ModuleGetStatutApresRachat($data_elements, $mnote, 8, 10);

                if (isset($result) and !empty($result)) {
                    $mnote->setStatutS2(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_s2'])
                    );
                    $mnote->setStatutAff(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_aff'])
                    );
                    $mnote->setStatutDef(
                        $this->em->getRepository(PeStatut::class)->find($result['statut_def'])
                    );
                }
            }
        }
        
        $this->em->flush();
        return new JsonResponse("Bien enregistre", 200);

    }

    public function ModuleGetStatutAvantRachat($mnote, $note_eliminatoire, $note_validation, $min_element_module_statut_def, $max_element_module_statut_def, $max_element_module_statut_aff) {


        $send_data = array();
//        if ($data->statut_aff == 60 || $data->statut_aff == 62) {
//            
//        }
//        else{
        if($min_element_module_statut_def == 52 || $max_element_module_statut_aff == 52){
            $send_data['statut_s2'] = 53;
            $send_data['statut_def'] = 53;
            $send_data['statut_aff'] = 53;
        }
        else{
            if ($mnote->getNote() < $note_eliminatoire || $min_element_module_statut_def == 16) {
                $send_data['statut_s2'] = 29;
                $send_data['statut_def'] = 29;
                $send_data['statut_aff'] = 29;
            } else {
                if ($mnote->getNote() < $note_validation) {
                    $send_data['statut_s2'] = 31;
                    $send_data['statut_def'] = 31;
                    $send_data['statut_aff'] = 31;
                } else {

                    switch ($min_element_module_statut_def) {
                        case 52:
                            $send_data['statut_s2'] = 53;
                            $send_data['statut_def'] = 53;
                            $send_data['statut_aff'] = 53;
                            break;
                        case 18:
                            $send_data['statut_s2'] = 32;
                            $send_data['statut_def'] = 32;
                            $send_data['statut_aff'] = 55;
                            break;
                        case 19:
                            $send_data['statut_s2'] = 32;
                            $send_data['statut_def'] = 32;
                            if ($max_element_module_statut_aff == 54) {
                                $send_data['statut_aff'] = 55;
                            } else {
                                $send_data['statut_aff'] = 34;
                            }
                            break;
                        case 21:
                            if ($max_element_module_statut_def == 21) {
                                $send_data['statut_s2'] = 34;
                                $send_data['statut_def'] = 34;
                                $send_data['statut_aff'] = 34;
                            } else {
                                $send_data['statut_s2'] = 55;
                                $send_data['statut_def'] = 55;
                                $send_data['statut_aff'] = 55;
                            }
                            break;
                        case 22:
                            $send_data['statut_s2'] = 35;
                            $send_data['statut_def'] = 35;
                            $send_data['statut_aff'] = 35;
                            break;
                        case 54:
                            $send_data['statut_s2'] = 55;
                            $send_data['statut_def'] = 55;
                            $send_data['statut_aff'] = 55;
                            break;
                    }
                }
            }
        }
//        }
        return $send_data;
    }

    public function ModuleGetStatutApresRachat($data, $mnote, $note_eliminatoire, $note_validation) {
        $send_data = array();
        foreach ($data as $key => $value) {
            if ($value->getStatutAff()->getId() == 17 || $value->getStatutDef()->getId() == 20) {
                if ($mnote->getNote() < $note_validation) {
                    $send_data['statut_s2'] = 30;
                    $send_data['statut_def'] = 30;
                    $send_data['statut_aff'] = 30;
                    break;
                } else {
                    $send_data['statut_s2'] = 33;
                    $send_data['statut_def'] = 33;
                    $send_data['statut_aff'] = 33;
                    break;
                }
            }
        }
        return $send_data;
    }
    
}
