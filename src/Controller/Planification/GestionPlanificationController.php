<?php

namespace App\Controller\Planification;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\ApiController;
use App\Entity\AcEtablissement;
use App\Controller\DatatablesController;
use App\Entity\AcAnnee;
use App\Entity\ISeance;
use App\Entity\PEnseignant;
use App\Entity\PGrade;
use App\Entity\PlEmptime;
use App\Entity\PlEmptimens;
use App\Entity\Semaine;
use App\Entity\TInscription;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/planification/gestions')]
class GestionPlanificationController extends AbstractController
{
    
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'gestion_planification')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'gestion_planification', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $professeurs = $this->em->getRepository(PEnseignant::class)->findAll();
        $grades = $this->em->getRepository(PGrade::class)->findAll();
        $semaines = $this->em->getRepository(Semaine::class)->findAll();
        return $this->render('planification/gestion_planification.html.twig', [
            'etablissements' => $etbalissements,
            'operations' => $operations,
            'semaines' => $semaines,
            'grades' => $grades,
            'professeurs' => $professeurs,
        ]);
    }
    
    #[Route('/list', name: 'list_gestion_planification')]
    public function list_gestion_planification(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where ann.validation_academique = 'non' and emp.active = 1 ";
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and frm.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and prom.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[3]['search']['value'])) {
            $filtre .= " and sem.id = '" . $params->all('columns')[3]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[4]['search']['value'])) {
            $filtre .= " and mdl.id = '" . $params->all('columns')[4]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[5]['search']['value'])) {
            $filtre .= " and elm.id = '" . $params->all('columns')[5]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[6]['search']['value'])) {
            $filtre .= " and sm.id = '" . $params->all('columns')[6]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[7]['search']['value'])) {
            $filtre .= " and ens.id = '" . $params->all('columns')[7]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[8]['search']['value'])) {
            $filtre .= " and grd.id = '" . $params->all('columns')[8]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[9]['search']['value']) || $params->all('columns')[9]['search']['value'] == 0) {
            $filtre .= " and emp.annuler = '" . $params->all('columns')[9]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[10]['search']['value']) || $params->all('columns')[10]['search']['value'] == 0) {
            $filtre .= " and emp.valider = '" . $params->all('columns')[10]['search']['value'] . "' ";
        } 
        $columns = array(
            array( 'db' => 'emp.id','dt' => 0 ),
            array( 'db' => 'emp.code','dt' => 1),
            array( 'db' => 'Concat(date(emp.start)," ", DATE_FORMAT(emp.heur_db, "%H:%i"),"-",DATE_FORMAT(emp.heur_fin, "%H:%i"))','dt' => 2),
            array( 'db' => 'emp.description','dt' => 3),
            array( 'db' => 'etab.abreviation','dt' => 4),
            array( 'db' => 'Upper(frm.abreviation)','dt' => 5),
            array( 'db' => 'lower(ann.designation)','dt' => 6),
            array( 'db' => 'prom.designation','dt' => 7),
            array( 'db' => 'Upper(sem.designation)','dt' => 8),
            array( 'db' => 'Upper(mdl.designation)','dt' => 9),
            array( 'db' => 'lower(elm.designation)','dt' => 10),
            array( 'db' => 'Upper(nat.abreviation)','dt' => 11),
            array( 'db' => 'Hour(SUBTIME(emp.heur_fin,emp.heur_db))','dt' => 12),
            array( 'db' => 'emp.valider','dt' => 13),
        );
        $sql = "SELECT DISTINCT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM pl_emptime emp
        INNER join pr_programmation prg on prg.id = emp.programmation_id
        INNER join pnature_epreuve nat on nat.id = prg.nature_epreuve_id
        INNER join ac_element elm on elm.id = prg.element_id
        INNER join ac_module mdl on mdl.id = elm.module_id
        INNER join ac_semestre sem on sem.id = mdl.semestre_id
        INNER join ac_promotion prom on prom.id = sem.promotion_id
        inner join ac_formation frm on frm.id = prom.formation_id
        -- INNER JOIN ac_annee ann ON ann.formation_id = frm.id
        INNER JOIN ac_annee ann ON ann.id = prg.annee_id
        INNER join ac_etablissement etab on etab.id = frm.etablissement_id
        INNER join semaine sm on sm.id = emp.semaine_id
        left join pl_emptimens emen on emen.seance_id = emp.id
        left join penseignant ens on ens.id = emen.enseignant_id
        left join pgrade grd on grd.id = ens.grade_id $filtre ";
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $this->em->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());
        
        $my_columns = DatatablesController::Pluck($columns, 'db');
        
        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $sqlRequest .= DatatablesController::Order($request, $columns);
        
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        $data = array();
        
        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            $nestedData[] = $i;
            $etat_bg="";
            foreach (array_values($row) as $key => $value) { 
                $checked = "";
                if ($key == 0) {
                    $nestedData[] = "<input type ='checkbox' class='check_seance' data-id ='$cd' >";
                }elseif($key == 13){
                    $nestedData[] = $value == 1 ? 'oui' : 'non';
                    $etat_bg = $value == 1 ? "etat_bg_reg" : "";
                }
                else{
                    $nestedData[] = $value;
                }
                $active = $this->em->getRepository(PlEmptime::class)->find($cd);
                $etat_bg = $active->getAnnuler() == 0 ? $etat_bg : "etat_bg_nf";
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $etat_bg; 
            $data[] = $nestedData;
            $i++;
        }
        // dd($nestedData);
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        return new Response(json_encode($json_data));
    }
    
    #[Route('/gestion_delete_planning', name: 'gestion_delete_planning')]
    public function gestion_delete_planning(Request $request): Response
    {   
        $ids = json_decode($request->get('ids_planning'));
        if (count($ids) == 0) {
            return new Response('Merci de choisir Au moins une Seance!',500);
        }
        foreach ($ids as $id) {
            $emptime = $this->em->getRepository(PlEmptime::class)->find($id);
            if ($emptime) {
                $iseances = $this->em->getRepository(ISeance::class)->findBy(['seance'=>$emptime]);
                foreach($iseances as $iseance){
                    $iseance->setStatut(5);
                }
                $emptime->setActive(0);
                $this->em->flush();
            }
        }
        return new Response('Seances Bien Supprimer',200);
    } 
    #[Route('/gestion_annuler_planning/{emptime}', name: 'gestion_annuler_planning')]
    public function gestion_annuler_planning(Request $request,PlEmptime $emptime): Response
    {   
        // $ids = json_decode($request->get('ids_planning'));
        if ($emptime->getValider() == 1) {
            return new Response('Impossible d\'annuler une séance validée! ',500);
        }elseif ($emptime->getAnnuler() == 1) {
            return new Response('Cette séance est déja annuler! ',500);
        }
        if ($request->get('motif_annuler') == "Autre") {
            if ($request->get('autre_motif') == "") {
                return new Response('Merci d\'entrer Le Motif d\'annulation ',500);
            }
            $motif = $request->get('autre_motif');
        }else {
            $motif = $request->get('motif_annuler');
        }
        // if (count($ids) == 0) {
        //     return new Response('Merci de choisir Au moins une Seance!',500);
        // }
        // if (empty($request->get('motif_annuler'))) {
        //     return new Response('Merci de Choisir le motif d\'annulation!',500);
        // }
        
        // foreach ($ids as $id) {
            // $emptime = $this->em->getRepository(PlEmptime::class)->find($id);
            // if ($emptime) {
                // if ($emptime->getValider() != 0) {
                    $emptime->setAnnuler(1);
                    $emptime->setMotifAnnuler($motif);
                    $this->em->flush();
                // }
            // }
        // }
        return new Response('Seances Bien Anuller',200);
    }  
    #[Route('/gestion_valider_planning/{emptime}', name: 'gestion_valider_planning')]
    public function gestion_valider_planning(Request $request,PlEmptime $emptime): Response
    {   
        // $ids = json_decode($request->get('ids_planning'));
        // if (count($ids) == 0) {
        //     return new Response('Merci de choisir Au moins une Seance!',500);
        // }
        // dd($emptime->getAnnuler());
        if ($emptime->getAnnuler() == 1) {
            return new Response('Impossible de valider une séance annulé! ',500);
        }
        // foreach ($ids as $id) {
        //     $emptime = $this->em->getRepository(PlEmptime::class)->find($id);
            if ($emptime) {
                $emptime->setValider(1);
                $this->em->flush();
            }
        // }
        return new Response('Seances Bien Valider',200);
    }  
    
    #[Route('/GetAbsenceByGroupe_gestion/{emptime}', name: 'GetAbsenceByGroupe_gestion')]
    public function GetAbsenceByGroupe_gestion(PlEmptime $emptime)
    {   
        $element = $emptime->getProgrammation()->getElement();
        $promotion = $element->getModule()->getSemestre()->getPromotion();
        $annee = $this->em->getRepository(AcAnnee::class)->findOneBy([
            'formation'=>$promotion->getFormation(),
            'validation_academique'=>'non',
            'cloture_academique'=>'non',
        ]);
        if( $emptime->getGroupe() != NULL){
            $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromoAndGroupe($promotion,$annee,$emptime->getGroupe());
            
        }else{
            $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromoNoGroup($promotion,$annee);
        }
        // $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromoAndGroupe($promotion,$annee,$emptime->getGroupe());
        $emptimenss = $this->em->getRepository(PlEmptimens::class)->findBy(['seance'=>$emptime]);
        $html = $this->render("planification/pdfs/absence.html.twig", [
            'inscriptions' => $inscriptions,
            'seance' => $emptime,
            'annee' => $annee,
            'emptimenss' => $emptimenss
        ])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '5',
            'margin_right' => '5',
            ]);
        $mpdf->SetTitle('Fiche D\'abcense');
        $mpdf->SetHTMLFooter(
            $this->render("planification/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("Fiche D'abcense.pdf", "I");
    }
    
    // #[Route('/getEnseignantByseance/{emptime}', name: 'getEnseignantByseance')]
    // public function getEnseignantByseance(PlEmptime $emptime)
    // {   
    //     $emptimenss = $this->em->getRepository(PlEmptimens::class)->findBy(['seance'=>$emptime]);
    //     $enseignants = [];
    //     foreach ( $emptimenss as $enseignant) {
    //         array_push($enseignants,$enseignant->getenseignant()->getId());
    //     }
    //     return new JsonResponse($enseignants,200);
        
    // }
    #[Route('/Getsequence_gestion/{emptime}', name: 'Getsequence_gestion')]
    public function Getsequence_gestion(PlEmptime $emptime)
    {   
        $promotion = $emptime->getProgrammation()->getElement()->getModule()->getSemestre()->getPromotion();
        // $annee = $this->em->getRepository(AcAnnee::class)->findOneBy([
        //     'formation'=>$promotion->getFormation(),
        //     'validation_academique'=>'non',
        //     'cloture_academique'=>'non',
        // ]);
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromoAndGroupe($promotion,$annee,$emptime->getGroupe());
        $diff = $emptime->getEnd()->diff($emptime->getStart());
        $hours = $diff->h;
        $hours = $hours + ($diff->days*24);
        $emptimenss = $this->em->getRepository(PlEmptimens::class)->findBy(['seance'=>$emptime]);
        $html = "";
        $i=1;
        foreach ($emptimenss as $emptimens) {
            $html .= $this->render("planification/pdfs/sequence.html.twig", [
                'seance' => $emptime,
                'annee' => $annee,
                'emptimenss' => $emptimenss,
                'emptimens' => $emptimens,
                'hours' => $hours,
                'effectife' => count($inscriptions),
            ])->getContent();
            $i < count($emptimenss) ? $html .= '<page_break>':"";
            $i++;
        }
        $mpdf = new Mpdf([
            // 'mode' => 'utf-8',
            'margin_top' => '8',
            'margin_left' => '5',
            'margin_right' => '5',
        ]);
        $mpdf->SetTitle('Fiche D\'abcense');
        $mpdf->SetHTMLFooter(
            $this->render("planification/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("Fiche D'abcense.pdf", "I");
    }  
}
