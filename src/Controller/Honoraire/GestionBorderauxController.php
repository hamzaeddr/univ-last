<?php

namespace App\Controller\Honoraire;

use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\AcEtablissement;
use App\Entity\HAlbhon;
use App\Entity\PEnseignant;
use App\Entity\PGrade;
use App\Entity\Semaine;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as reader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/honoraire/gestion_borderaux')]
class GestionBorderauxController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'gestion_borderaux')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'gestion_borderaux', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $semaines = $this->em->getRepository(Semaine::class)->findAll();
        return $this->render('honoraire/gestion_borderaux.html.twig', [
            'etablissements' => $etbalissements,
            'operations' => $operations,
            'semaines' => $semaines,
        ]);
    }

    
    #[Route('/list', name: 'list_gestion_borderaux')]
    public function list_gestion_borderaux(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where emp.annuler = 0 ";
        
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
            $filtre .= " and sm.id = '" . $params->all('columns')[3]['search']['value'] . "' ";
        } 
        
        $columns = array(
            array( 'db' => 'alb.id','dt' => 0 ),
            array( 'db' => 'alb.code','dt' => 1),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from  halbhon alb
        INNER JOIN hhonens hon ON hon.bordereau_id = alb.id 
        INNER JOIN pl_emptime emp on emp.id = hon.seance_id
        INNER JOIN semaine sm on sm.id = emp.semaine_id
        INNER JOIN pr_programmation prog ON prog.id = emp.programmation_id
        INNER JOIN pnature_epreuve nat on nat.id = prog.nature_epreuve_id
        INNER JOIN ac_element ele ON ele.id = prog.element_id 
        INNER JOIN ac_module mdl ON mdl.id =  ele.module_id
        INNER JOIN ac_semestre sem ON sem.id =  mdl.semestre_id
        INNER JOIN ac_promotion prm ON prm.id = sem.promotion_id
        INNER JOIN ac_formation frm ON frm.id = prm.formation_id
        INNER JOIN ac_etablissement etab ON etab.id = frm.etablissement_id
        $filtre GROUP BY alb.id";
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
                    $nestedData[] = "<input $checked type ='checkbox' data-id ='$cd' >";
                }
                else{
                    $nestedData[] = $value;
                }
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $etat_bg; 
            $data[] = $nestedData;
            $i++;
        }
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        return new Response(json_encode($json_data));
    }
    
    #[Route('/annuler_borderaux', name: 'annuler_borderaux')]
    public function annuler_borderaux(Request $request): Response
    {
        $ids = json_decode($request->get('ids_borderaux'));
        if($ids == NULL){
            return new JsonResponse('Merci de Choisir au moins une ligne!',500);
        }
        foreach ($ids as $id) {
            $halbhon = $this->em->getRepository(HAlbhon::class)->find($id);
            foreach ($halbhon->getHonenss() as $honens) {
                $honens->setBordereau(Null);
                $this->em->flush();
            }
        }
        return new JsonResponse('Bordereaux Bien Supprimer',200);
    }
    
    #[Route('/exporter_borderaux', name: 'exporter_borderaux')]
    public function exporter_borderaux(Request $request, SluggerInterface $slugger): Response
    {
        $ids = json_decode($request->get('ids_borderaux'));
        if($ids == NULL){
            return new JsonResponse('Merci de Choisir au moins une ligne!',500);
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'N°');
        $sheet->setCellValue('B1', 'ID_BE');
        $sheet->setCellValue('C1', 'ID_COURS');
        $sheet->setCellValue('D1', 'Etablissement');
        $sheet->setCellValue('E1', 'Formation');
        $sheet->setCellValue('F1', 'Promotion');
        $sheet->setCellValue('G1', 'DATE_BE');
        $sheet->setCellValue('H1', 'Semaine');
        $sheet->setCellValue('I1', 'PROFESSEUR');
        $sheet->setCellValue('J1', 'D_SEANCE');
        $sheet->setCellValue('K1', 'DUREE');
        $sheet->setCellValue('L1', 'HR_D');
        $sheet->setCellValue('M1', 'HR_F');
        $sheet->setCellValue('N1', 'CLS_GRP');
        $sheet->setCellValue('O1', 'MODULE/MATIERE');
        $sheet->setCellValue('P1', 'TYPE');
        $sheet->setCellValue('Q1', 'GENRE');
        $sheet->setCellValue('R1', 'EFFECTIVE');
        $sheet->setCellValue('S1', 'MT');
        $i=2;
        foreach ($ids as $id) {
            $halbhon = $this->em->getRepository(HAlbhon::class)->find($id);
            foreach ($halbhon->getHonenss() as $honens) {
                $element = $honens->getSeance()->getProgrammation()->getElement();
                $semaine = 'Semaine '.$honens->getSeance()->getSemaine()->getNsemaine().' de :' .$honens->getSeance()->getSemaine()->getDateDebut()->format('d/m').' à '.$honens->getSeance()->getSemaine()->getDateFin()->format('d/m').' '.$honens->getSeance()->getSemaine()->getDateDebut()->format('Y');
                
                $sheet->setCellValue('A'.$i, $i-1);
                $sheet->setCellValue('B'.$i, $halbhon->getCode());
                $sheet->setCellValue('C'.$i, $honens->getSeance()->getCode());
                $sheet->setCellValue('D'.$i, $element->getModule()->getSemestre()->getPromotion()->getFormation()->getEtablissement()->getDesignation());
                $sheet->setCellValue('E'.$i, $element->getModule()->getSemestre()->getPromotion()->getFormation()->getDesignation());
                $sheet->setCellValue('F'.$i, $element->getModule()->getSemestre()->getPromotion()->getDesignation());
                $sheet->setCellValue('G'.$i, $halbhon->getCreated()->format('d/m/Y'));
                $sheet->setCellValue('H'.$i, $semaine);
                $sheet->setCellValue('I'.$i, $honens->getEnseignant()->getNom() .' '.$honens->getEnseignant()->getPrenom());
                $sheet->setCellValue('J'.$i, $honens->getSeance()->getStart()->format('j/m/Y'));
                $diff = $honens->getSeance()->getEnd()->diff($honens->getSeance()->getStart());
                $hours = $diff->h;
                $hours = $hours + ($diff->days*24);
                $sheet->setCellValue('K'.$i, $hours);
                $sheet->setCellValue('L'.$i, $honens->getSeance()->getStart()->format('H:i'));
                $sheet->setCellValue('M'.$i, $honens->getSeance()->getEnd()->format('H:i'));
                $sheet->setCellValue('N'.$i, '');
                $sheet->setCellValue('O'.$i, $element->getModule()->getDesignation());
                $sheet->setCellValue('P'.$i, $honens->getSeance()->getProgrammation()->getNatureEpreuve()->getDesignation());
                $sheet->setCellValue('Q'.$i, $element->getNature()->getDesignation());
                $sheet->setCellValue('R'.$i, '');
                $sheet->setCellValue('S'.$i, number_format($honens->getMontant(), 2, ',', ' '));
                $i++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Report_2.xlsx';
        $writer->save($this->getParameter('honoraire_export_directory').'/'.$fileName);
        return new JsonResponse($fileName,200);
    }
}
