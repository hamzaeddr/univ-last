<?php

namespace App\Controller\Etudiant;

use App\Controller\ApiController;
use DateTime;
use App\Entity\PStatut;
use App\Entity\XTypeBac;
use App\Entity\TEtudiant;
use App\Entity\TPreinscription;
use App\Entity\XAcademie;
use App\Entity\NatureDemande;
use App\Entity\AcAnnee;
use App\Controller\DatatablesController;
use App\Entity\XFiliere;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Null_;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/etudiant/appel')]
class CentreDappelController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'centre_appel_index')]
    public function index(Request $request): Response
    {
        // dd('test');
        //check if user has access to this page
        $operations = ApiController::check($this->getUser(), 'centre_appel_index', $this->em, $request);
        // dd($operations);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $filieres = $this->em->getRepository(XFiliere::class)->findBy(['active'=>1]);
        $typebacs = $this->em->getRepository(XTypeBac::class)->findBy(['active'=>1]);
        return $this->render('etudiant/centre_appel/index.html.twig', [
            'operations' => $operations,
            'filieres' => $filieres,
            'typebacs' => $typebacs,
        ]);
    }
    
    #[Route('/list', name: 'appel_list')]
    public function list(Request $request): Response
    {
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 ";
        // if (!empty($params->all('columns')[0]['search']['value'])) {
        //     $filtre .= " and grp.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        // }
        
        $columns = array(
            array( 'db' => 'etu.id','dt' => 0 ),
            array( 'db' => 'etu.nom','dt' => 1),
            array( 'db' => 'etu.prenom','dt' => 2),
            array( 'db' => 'etu.tel1','dt' => 3),
            array( 'db' => 'etu.tel_pere','dt' => 4),
            array( 'db' => 'etu.tel_mere','dt' => 5),
            array( 'db' => 'etu.annee_bac','dt' => 6),
            array( 'db' => 'etu.moyenne_bac','dt' => 7),
            array( 'db' => 'LOWER(xtb.designation)','dt' => 8),
            array( 'db' => 'UPPER(fil.abreviation)','dt' => 9),
            array( 'db' => 'etu.tele_liste','dt' => 10),
            array( 'db' => 'etu.obs','dt' => 11),
            array( 'db' => 'etu.rdv1','dt' => 12),
            array( 'db' => 'etu.rdv2','dt' => 13)
        );
        // dd($columns);
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                FROM tetudiant etu
                left join pstatut st on st.id = etu.statut_id
                left join nature_demande nd on nd.id = etu.nature_demande_id
                left join xtype_bac xtb on xtb.id = etu.type_bac_id 
                left join xfiliere fil on fil.id = etu.filiere_id  
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
        $sqlRequest .= DatatablesController::Order($request, $columns);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        $data = array();
        $k = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            $nestedData[] = $k++;
            $i = 0;
            foreach (array_values($row) as $key => $value) {
                if ($key > 0 ) {
                    $nestedData[] = $value;
                }
                $i++;
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $cd;
            $data[] = $nestedData;
        }
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        // die;
        return new Response(json_encode($json_data));
    }

    #[Route('/getAppelRdv_appel/{etudiant}', name: 'getAppelRdv_appel')]
    public function getAppelRdv_appel(Request $request, TEtudiant $etudiant) 
    {
        $rdv1 = $etudiant->getRdv1() == Null ? "" : $etudiant->getRdv1()->format('Y-m-j');
        $rdv2 = $etudiant->getRdv2() == Null ? "" : $etudiant->getRdv2()->format('Y-m-j');
        $appelrdv = [ 
                'rdv1' => $rdv1,
                'rdv2' => $rdv2,
                // 'statut_appel' => $etudiant->getStatut(),
                // 'obs' => $$etudiant->getObs(),
        ];
        return new JsonResponse($appelrdv);
    }

    #[Route('/rdvappel/{etudiant}', name: 'rdvappel')]
    public function rdvappel(Request $request, TEtudiant $etudiant) 
    {
        // // dd($request);
        // if (empty($request->get('dateappelle')) || empty($request->get('rdv1')) ||
        //  empty($request->get('rdv2')) || empty($request->get('statut_appel')) || empty($request->get('Observation'))) {
        //     return new JsonResponse('Merci de choisir l')
        // }
        $etudiant->setTeleListe($request->get('dateappelle'));
        $rdv1 = $request->get('rdv1') == "" ? NULL : new \DateTime($request->get('rdv1'));
        $rdv2 = $request->get('rdv1') == "" ? NULL : new \DateTime($request->get('rdv1'));
        $etudiant->setRdv1($rdv1);
        $etudiant->setRdv2($rdv2);
        $etudiant->setTeleListe($request->get('statut_appel'));
        $etudiant->setObs($request->get('Observation'));

        // if ($request->get('annee_bac') != "") {
        //     $etudiant->setAnneeBac($request->get('annee_bac'));
        // }
        // if ($request->get('note_bac') != "") {
        //     $etudiant->setMoyenneBac($request->get('note_bac'));
        // }
        // if ($request->get('type_bac') != "") {
        //     $etudiant->setTypeBac($this->em->getRepository(XTypeBac::class)->find($request->get('type_bac')));
        // }
        // if ($request->get('filiere') != "") {
        //     $etudiant->setFiliere($this->em->getRepository(XFiliere::class)->find($request->get('filiere')));
        // }
        $etudiant->setChoix($request->get('choix'));
        $etudiant->setOperateur($this->getUser());
        $this->em->flush();
        return new JsonResponse("Bien enregistre");
    }
    
    
    #[Route('/extraction_appels', name: 'extraction_appels')]
    public function extraction_appels()
    {   
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ORD');
        $sheet->setCellValue('B1', 'NOM');
        $sheet->setCellValue('C1', 'PRENOM');
        $sheet->setCellValue('D1', 'TEL1');
        $sheet->setCellValue('E1', 'TEL2');
        $sheet->setCellValue('F1', 'TEL3');
        $sheet->setCellValue('G1', 'ANNEE BAC');
        $sheet->setCellValue('H1', 'NOTE');
        $sheet->setCellValue('I1', 'TYPE DE BAC');
        $sheet->setCellValue('J1', 'FILIERE');
        $sheet->setCellValue('K1', 'STATUT');
        $sheet->setCellValue('L1', 'OBSERVATION');
        $sheet->setCellValue('M1', 'RDV1');
        $sheet->setCellValue('N1', 'RDV2');
        $sheet->setCellValue('O1', 'OPERATEUR');
        $sheet->setCellValue('P1', 'Choix');
        $i=2;
        $j=1;
        // $current_year = date('m') > 7 ? $current_year = date('Y').'/'.date('Y')+1 : $current_year = date('Y') - 1 .'/' .date('Y');
        $current_year = "2022/2023";
        $etudiants = $this->em->getRepository(TEtudiant::class)->getEtudiantByCurrentYear($current_year);
        // dd($etudiants);
        foreach ($etudiants as $etudiant) {
            $sheet->setCellValue('A'.$i, $j);
            $sheet->setCellValue('B'.$i, $etudiant->getNom());
            $sheet->setCellValue('C'.$i, $etudiant->getPrenom());
            $sheet->setCellValue('D'.$i, $etudiant->getTel1());
            $sheet->setCellValue('E'.$i, $etudiant->getTelPere());
            $sheet->setCellValue('F'.$i, $etudiant->getTelMere());
            $sheet->setCellValue('G'.$i, $etudiant->getAnneeBac());
            $sheet->setCellValue('H'.$i, $etudiant->getMoyenneBac());
            if ($etudiant->getTypeBac() != null) {
                $sheet->setCellValue('I'.$i, $etudiant->getTypeBac()->getDesignation());
            }
            if ($etudiant->getFiliere() != null) {
                $sheet->setCellValue('J'.$i, $etudiant->getFiliere()->getDesignation());
            }
            $sheet->setCellValue('K'.$i, $etudiant->getTeleListe());
            $sheet->setCellValue('L'.$i, $etudiant->getObs());
            $sheet->setCellValue('M'.$i, $etudiant->getRdv1());
            $sheet->setCellValue('N'.$i, $etudiant->getRdv2());
            if ($etudiant->getOperateur() != NULL) {
                $sheet->setCellValue('O'.$i, $etudiant->getOperateur()->getUserName());
            }
            $sheet->setCellValue('P'.$i, $etudiant->getChoix());
            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Extraction Etudiants.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    
}
