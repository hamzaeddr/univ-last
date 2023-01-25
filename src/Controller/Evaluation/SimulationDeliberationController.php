<?php

namespace App\Controller\Evaluation;

use App\Controller\ApiController;
use App\Entity\AcAnnee;
use App\Entity\AcEtablissement;
use App\Entity\AcModule;
use App\Entity\AcSemestre;
use App\Entity\ExControle;
use App\Entity\ExEnotes;
use App\Entity\ExMnotes;
use App\Entity\ExSnotes;
use App\Entity\TInscription;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evaluation/simulationdeliberation')]
class SimulationDeliberationController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    
    #[Route('/', name: 'evaluation_simulation_deliberation')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'evaluation_simulation_deliberation', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etablissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        return $this->render('evaluation/simulation_deliberation/index.html.twig',[
            'operations' => $operations,
            'etablissements' => $etablissements,
        ]);
    }
    #[Route('/list/{semestre}', name: 'evaluation_simulation_deliberation_list')]
    public function evaluationSimulationDeliberationList(Request $request, AcSemestre $semestre): Response
    {
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($semestre->getPromotion()->getFormation());
        $verify = $this->em->getRepository(ExControle::class)->checkIfyoucanDelibreSemestre($annee, $semestre);

        $check = 0; //valider cette opération
        if(!$verify){
            $check = 1; //opération déja validé
        }
        
        $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromo($annee, $semestre->getPromotion(), null);
        $data_saved = [];
        $modules = $this->em->getRepository(AcModule::class)->getMdouleBySemestreAndExControle($semestre, $annee);
        foreach ($inscriptions as $inscription) {
            $moyenne = 0;
            $moyenne_normal = 0;
            $moyenne_rachat = 0;
            $total_coef = 0;
            $total_coef_normal = 0;
            $noteModules = [];
            foreach ($modules as $module) {
                $total_coef += $module->getCoefficient();
                $mnote = $this->em->getRepository(ExMnotes::class)->findOneBy(['module' => $module, 'inscription' => $inscription]);
    
                $moyenne += $mnote->getNote() * $module->getCoefficient();
                
                if ($module->getType() == 'N') {
                    $moyenne_normal += $mnote->getNote() * $module->getCoefficient();
                    $total_coef_normal += $module->getCoefficient();
                    if ($mnote->getNoteRachat()) {
                        $moyenne_rachat += $mnote->getNoteRachat() *  $module->getCoefficient();
                    }
                }
                $st = "";
                // if($inscription->getCodeAnonymat() == 10732 && $module->getId() == 925){
                //     dd($mnote);
                // }
                if ($mnote->getStatutAff() && $mnote->getStatutAff()->getId() == 29) {
                    $st = "color:red; font-weight: bold;";
                }
                if ($mnote->getStatutAff() == true && $mnote->getStatutAff()->getId() == 26) {
                    $st = "color:black; font-weight: bold;";
                }

                array_push($noteModules, [
                    'note' => $mnote->getNote(),
                    'style' => $st,
                    // 'module' => $module,
                ]);

            }
            $snote = $this->em->getRepository(ExSnotes::class)->findOneBy(['semestre' => $semestre, 'inscription' => $inscription]);
            $stSemestre = "";
            if ($snote->getStatutAff() && $snote->getStatutAff()->getId() == 57) {
                $stSemestre = "color:red; font-weight: bold;";
            }
            if ($snote->getStatutAff() && $snote->getStatutAff()->getId() == 39) {
                $stSemestre = "color:black; font-weight: bold;";
            }
            $moyenne = ($moyenne / $total_coef);
            $moyenneNormal = ($moyenne_normal / $total_coef_normal);
            $moyenneRachat = ($moyenne_rachat / $total_coef_normal);
            
            array_push($data_saved, [

                'inscription' => $inscription,
                'noteModules' => $noteModules,
                'moyenneNormal' =>$moyenneNormal, 
                'moyenneRachat' =>$moyenneRachat, 
                'moyenneSec' => $moyenne,
                'snoteRachat' => $snote->getNoteRachat(),
                'styleSemestre' => $stSemestre
            ]);
        }
        // dd($data_saved);
        $session = $request->getSession();
        $session->set('data_deliberation', [
            'data_saved' => $data_saved, 
            'modules' => $modules,
            'semestre' => $semestre
        ]);
        $html = $this->render('evaluation/simulation_deliberation/pages/list_epreuve_normal.html.twig', [
            'data_saved' => $data_saved,
            'modules' => $modules
        ])->getContent();
        // dd($html);
        return new JsonResponse(['html' => $html, 'check' => $check]);
    } 

    #[Route('/simuler/{inscription}/{semestre}', name: 'evaluation_simulation_deliberation_simuler')]
    public function evaluationSimulationSimuler(TInscription $inscription, AcSemestre $semestre)
    {
        // $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($semestre->getPromotion()->getFormation());
        // $modules = $this->em->getRepository(AcModule::class)->getMdouleBySemestreAndExControle($semestre, $annee);
        $snote = $this->em->getRepository(ExSnotes::class)->findOneBy(["semestre" => $semestre, "inscription" => $inscription]);
        $html = $this->render('evaluation/simulation_deliberation/pages/simuler.html.twig', [
            'snote' => $snote,
            'inscription' => $inscription,
            'semestre' => $semestre
        ])->getContent();
        
        return new JsonResponse($html);
    }
    #[Route('/saverachat', name: 'evaluation_simulation_deliberation_save')]
    public function evaluationSimulationSave(Request $request)
    {
        $data = json_decode($request->get("data"));
        $noteRachatSemestre = $data[0]->semestre;
        $noteRachatModules = $data[1]->modules;
        $noteRachatElements = $data[2]->elements;
        
        $snote = $this->em->getRepository(ExSnotes::class)->find($noteRachatSemestre->id);
        if($noteRachatSemestre->note_rachat == 0 || $noteRachatSemestre->note_rachat == ""){
            $snote->setNoteRachat(null);
        } else {
            $snote->setNoteRachat((float)$noteRachatSemestre->note_rachat);
        }
        foreach ($noteRachatModules as $noteModule) {
            $mnote = $this->em->getRepository(ExMnotes::class)->find($noteModule->id);
            if($noteModule->note_rachat == 0 || $noteModule->note_rachat == ""){
                $mnote->setNoteRachat(null);
            } else {
                $mnote->setNoteRachat((float)$noteModule->note_rachat);
            }
        }
        foreach ($noteRachatElements as $noteElement) {
            $enote = $this->em->getRepository(ExEnotes::class)->find($noteElement->id);
            if($noteElement->note_rachat == 0 || $noteElement->note_rachat == ""){
                $enote->setNoteRachat(null);
            } else {
                $enote->setNoteRachat((float)$noteElement->note_rachat);
            }
            if($noteElement->cc_rachat == 0 || $noteElement->cc_rachat == ""){
                $enote->setCcRachat(null);
            } else {
                $enote->setCcRachat((float)$noteElement->cc_rachat);
            }
            if($noteElement->tp_rachat == 0 || $noteElement->tp_rachat == ""){
                $enote->setTpRachat(null);
            } else {
                $enote->setTpRachat((float)$noteElement->tp_rachat);
            }
            if($noteElement->ef_rachat == 0 || $noteElement->ef_rachat == ""){
                $enote->setEfRachat(null);
            } else {
                $enote->setEfRachat((float)$noteElement->ef_rachat);
            }
        }
        // $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($semestre->getPromotion()->getFormation());
        // $modules = $this->em->getRepository(AcModule::class)->getMdouleBySemestreAndExControle($semestre, $annee);
        // $snote = $this->em->getRepository(ExSnotes::class)->findOneBy(["semestre" => $semestre, "inscription" => $inscription]);
        // $html = $this->render('evaluation/simulation_deliberation/pages/simuler.html.twig', [
        //     'snote' => $snote,
        //     'inscription' => $inscription,
        //     'semestre' => $semestre
        // ])->getContent();
        $this->em->flush();
        return new JsonResponse("Bien Enregistre");
    }
    #[Route('/valider', name: 'evaluation_simulation_deliberation_valider')]
    public function evaluationSemestreValider(Request $request) 
    {         
        $session = $request->getSession();
        $semestre = $session->get('data_deliberation')['semestre'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($semestre->getPromotion()->getFormation());
        
        $this->em->getRepository(ExControle::class)->updateSemestreBySimulation($semestre, $annee, 1);

        return new JsonResponse("Bien Valider", 200);
    }
    #[Route('/devalider', name: 'evaluation_simulation_deliberation_devalider')]
    public function evaluationSemestreDevalider(Request $request) 
    {         
        $session = $request->getSession();
        $semestre = $session->get('data_deliberation')['semestre'];
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($semestre->getPromotion()->getFormation());
        $this->em->getRepository(ExControle::class)->updateSemestreBySimulation($semestre, $annee, 0);
        $this->em->flush();

        return new JsonResponse("Bien Devalider", 200);
    }
}
