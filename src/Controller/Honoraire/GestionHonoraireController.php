<?php

namespace App\Controller\Honoraire;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\AcEtablissement;
use App\Entity\HHonens;
use App\Entity\HHonensAnnuler;
use App\Entity\PEnseignant;
use App\Entity\PGrade;
use App\Entity\PlEmptime;
use App\Entity\Semaine;
use App\Entity\PEnseignantExcept;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/honoraire/gestion')]
class GestionHonoraireController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'gestion_honoraire')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'gestion_honoraire', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $professeurs = $this->em->getRepository(PEnseignant::class)->findAll();
        $grades = $this->em->getRepository(PGrade::class)->findAll();
        $semaines = $this->em->getRepository(Semaine::class)->findAll();
        return $this->render('honoraire/gestion_honoraire.html.twig', [
            'etablissements' => $etbalissements,
            'operations' => $operations,
            'semaines' => $semaines,
            'grades' => $grades,
            'professeurs' => $professeurs,
        ]);
    }

    #[Route('/list', name: 'list_gestion_honoraire')]
    public function list_gestion_honoraire(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where 1=1 and hon.annuler = 0 and  ann.validation_academique = 'non' ";
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and frm.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and prm.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[3]['search']['value'])) {
            $filtre .= " and sem.id = '" . $params->all('columns')[3]['search']['value'] . "' ";
        } 
        
        if (!empty($params->all('columns')[4]['search']['value'])) {
            if ($params->all('columns')[4]['search']['value'] !== 'All') {
                $filtre .= " and hon.statut = '" . $params->all('columns')[4]['search']['value'] . "' ";
            }
        }
        if (!empty($params->all('columns')[5]['search']['value'])) {
            $filtre .= " and sm.id = '" . $params->all('columns')[5]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[6]['search']['value'])) {
            $filtre .= " and ens.id = '" . $params->all('columns')[6]['search']['value'] . "' ";
        }  
        if (!empty($params->all('columns')[7]['search']['value'])) {
            $filtre .= " and gr.id = '" . $params->all('columns')[7]['search']['value'] . "' ";
        }
        
        $columns = array(
            array( 'db' => 'hon.id','dt' => 0 ),
            array( 'db' => 'emp.code','dt' => 1),
            array( 'db' => 'Concat(date(emp.start)," ", DATE_FORMAT(emp.heur_db, "%H:%i"),"-",DATE_FORMAT(emp.heur_fin, "%H:%i"))','dt' => 2),
            array( 'db' => 'ens.nom','dt' => 3),
            array( 'db' => 'ens.prenom','dt' => 4),
            array( 'db' => 'lower(gr.designation)','dt' => 5),
            array( 'db' => 'Hour(SUBTIME(emp.heur_fin,emp.heur_db))','dt' => 6),
            array( 'db' => 'hon.montant','dt' => 7),
            array( 'db' => 'etab.abreviation','dt' => 8),
            array( 'db' => 'Upper(frm.abreviation)','dt' => 9),
            array( 'db' => 'lower(ann.designation)','dt' => 10),
            array( 'db' => 'prm.designation','dt' => 11),
            array( 'db' => 'Upper(sem.designation)','dt' => 12),
            array( 'db' => 'Upper(mdl.designation)','dt' => 13),
            array( 'db' => 'lower(ele.designation)','dt' => 14),
            array( 'db' => 'hon.statut','dt' => 15),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM hhonens hon
        INNER JOIN penseignant ens ON ens.id = hon.enseignant_id
        INNER JOIN pgrade gr ON gr.id = ens.grade_id
        INNER JOIN pl_emptime emp  ON hon.seance_id = emp.id
        INNER JOIN semaine sm  ON sm.id = emp.semaine_id
        INNER JOIN pr_programmation prog ON prog.id = emp.programmation_id
        INNER join pnature_epreuve nat on nat.id = prog.nature_epreuve_id
        INNER JOIN ac_element ele ON ele.id = prog.element_id 
        INNER JOIN ac_module mdl ON mdl.id =  ele.module_id
        INNER JOIN ac_semestre sem ON sem.id =  mdl.semestre_id
        INNER JOIN ac_promotion prm ON prm.id = sem.promotion_id
        INNER JOIN ac_formation frm ON frm.id = prm.formation_id
        INNER JOIN ac_annee ann ON ann.id = prog.annee_id
        INNER JOIN ac_etablissement etab ON etab.id = frm.etablissement_id
        $filtre ";
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
        // $sqlRequest .= DatatablesController::Order($request, $columns);
        $changed_column = $params->all('order')[0]['column'] > 0 ? $params->all('order')[0]['column'] -1 : 0;
        $sqlRequest .= " ORDER BY " .DatatablesController::Pluck($columns, 'db')[$changed_column] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";

        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        $data = [];
        
        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = [];
            $cd = $row['id'];
            $nestedData[] = $i;
            $etat_bg="";
            foreach (array_values($row) as $key => $value) { 
                $checked = "";
                if ($key == 0) {
                    if ($row['statut'] == 'A' || $row['statut'] == 'R') {
                        $checked = "checked='' disabled='' class='check_seance'";
                    }
                    $nestedData[] = "<input $checked type ='checkbox' data-id ='$cd' >";
                }
                
                // elseif($key == 12){
                //     $nestedData[] = $value;
                //     $nbr_sc_regroupe = $this->em->getRepository(PlEmptime::class)->getNbr_sc_regroupe($cd);
                //     $nbr_sc_regroupe = $nbr_sc_regroupe == 0 ? 1 : $nbr_sc_regroupe;
                //     $nestedData[] = "<a value='$cd' data-column = '" . $nbr_sc_regroupe . "' class= 'nbr_sc_regroupe nbr_sc_regroupe_" . $nbr_sc_regroupe . "'>" . $nbr_sc_regroupe . "</a>";
                // }
                else{
                    $nestedData[] = $value;
                }
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
    
    #[Route('/annuler_honoraires', name: 'annuler_honoraires')]
    public function annuler_honoraires(Request $request): Response
    {
        $ids = json_decode($request->get('ids_seances'));
        if($ids == NULL){
            return new JsonResponse('Merci de Choisir au moins une ligne',500);
        }
        foreach ($ids as $id) {
            $honens = $this->em->getRepository(HHonens::class)->find($id);
            $user = $honens->getUserCreated() == Null ? Null : $honens->getUserCreated()->getId();
            if ($honens->getStatut() == 'E') {
                $honensAnnuler = new HHonensAnnuler();
                $honensAnnuler->setEnseignant($honens->getEnseignant()->getId());
                $honensAnnuler->setSeance($honens->getSeance()->getId());
                $brd = $honens->getBordereau() == Null ? Null : $honens->getBordereau()->getId(); 
                $honensAnnuler->setBordereau($brd);
                $honensAnnuler->setCode($honens->getCode());
                $user = $honens->getUserCreated() == Null ? Null : $honens->getUserCreated()->getId();
                $honensAnnuler->setUserAnnuled($user);
                $honensAnnuler->setUserCreated($this->getUser()->getId());
                $honensAnnuler->setDateReglement($honens->getDateReglement());
                $honensAnnuler->setCreated(new \DateTime('now'));
                $honensAnnuler->setNbrHeur($honens->getNbrHeur());
                $honensAnnuler->setMontant($honens->getMontant());
                $honensAnnuler->setStatut('A');
                $honensAnnuler->setAnnuler(1);
                $honensAnnuler->setExept($honens->getExept());
                
                $this->em->remove($honens);
                $this->em->persist($honensAnnuler);
                $this->em->flush();
            }
        }
        return new JsonResponse('Toutes les seances sont annuler',200);
    }

    #[Route('/regle_honoraires', name: 'regle_honoraires')]
    public function regle_honoraires(Request $request): Response
    {
        $ids = json_decode($request->get('ids_seances'));
        if($ids == NULL){
            return new JsonResponse('Merci de Choisir au moins une ligne',500);
        }
        foreach ($ids as $id) {
            $honens = $this->em->getRepository(HHonens::class)->find($id);
            if ($honens->getStatut() == 'E') {
                $honens->setDateReglement(new \DateTime('now'));
                $honens->setStatut('R');
                $this->em->flush();
            }
        }
        return new JsonResponse('Toutes les seances sont Réglées',200);
    }

    
    // #[Route('/reporting_honoraire', name: 'reporting_honoraire')]
    // public function epreuveEnMasse(Request $request, SluggerInterface $slugger) 
    // {   
    //     $spreadsheet = new Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();
    //     $sheet->setCellValue('A1', 'Cours');
    //     $sheet->setCellValue('B1', 'D.Heure');
    //     $sheet->setCellValue('C1', 'Nom');
    //     $sheet->setCellValue('D1', 'Prenom');
    //     $sheet->setCellValue('E1', 'Grade');
    //     $sheet->setCellValue('F1', 'N.Heure');
    //     $sheet->setCellValue('G1', 'Montant');
    //     $sheet->setCellValue('H1', 'Etab');
    //     $sheet->setCellValue('I1', 'Form');
    //     $sheet->setCellValue('J1', 'Annee');
    //     $sheet->setCellValue('K1', 'Promo');
    //     $sheet->setCellValue('L1', 'Semes');
    //     $sheet->setCellValue('M1', 'Mdle');
    //     $sheet->setCellValue('N1', 'Elem');
    //     $sheet->setCellValue('O1', 'ST');
    //     // $gnotes = $this->em->getRepository(ExGnotes::class)->ExgnotesOrderByNom($epreuve);
    //     // foreach($gnotes as $gnote) {
    //     //     $sheet->setCellValue('A'.$i, $gnote->getInscription()->getId());
    //     //     $i++;
    //     // }

    //     // $writer = new Xlsx($spreadsheet);
    //     // $fileName = 'epreuves_'.$epreuve->getId().'.xlsx';
    //     // $temp_file = tempnam(sys_get_temp_dir(), $fileName);
    //     // $writer->save($temp_file);

    //     // return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    // }
}
