<?php

namespace App\Controller\Facture;

use DateTime;
use Mpdf\Mpdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\AcEtablissement;
use App\Entity\XModalites;
use App\Entity\TBrdpaiement;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\nuts;
use App\Entity\TReglement;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/facture/bordereau')]
class BordereauController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'gestion_bordereaux')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'gestion_bordereaux', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etablissements = $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $paiements = $this->em->getRepository(XModalites::class)->findby(['active'=>1]);
        return $this->render('facture/bordereau.html.twig', [
            'operations' => $operations,
            'etablissements' => $etablissements,
            'paiements' => $paiements,
        ]);
    }
    
    #[Route('/list', name: 'list_facture_borderaux')]
    public function list_facture_borderaux(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1=1 ";
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and pae.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }
        $columns = array(
            array( 'db' => 'brd.id','dt' => 0 ),
            array( 'db' => 'brd.code','dt' => 1),
            array( 'db' => 'etab.abreviation','dt' => 2),
            array( 'db' => 'etab.designation','dt' => 3),
            array( 'db' => 'lower(pae.designation)','dt' => 4),
            array( 'db' => 'brd.montant','dt' => 5),
            array( 'db' => 'DATE_FORMAT(brd.created,"%Y-%m-%d")','dt' => 6),
            array( 'db' => 'user.username','dt' => 7),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM tbrdpaiement brd 
        INNER JOIN xmodalites pae on pae.id = brd.modalite_id
        INNER join ac_etablissement etab on etab.id = brd.etablissement_id
        LEFT join users user on  user.id = brd.user_created_id $filtre ";
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
                if ($key > 0) {
                    $nestedData[] = $value;
                }
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = "";
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
    
    #[Route('/print_borderaux/{borderaux}', name: 'print_borderaux')]
    public function printborderaux(TBrdpaiement $borderaux)
    {  
        // $reglements = $borderaux->getReglements();
        $reglements = $this->em->getRepository(Treglement::class)->findBy(['bordereau'=>$borderaux,'annuler'=>0]);
        $reglementTotal = $this->em->getRepository(TReglement::class)->getReglementsSumMontant($borderaux);
        $reglementTotal = $reglementTotal['total'] < 0 ? $reglementTotal['total'] * -1 : $reglementTotal['total'];
        $obj = new nuts( $reglementTotal, "MAD");
        $text = $obj->convert("fr-FR");
        $html = $this->render("facture/pdfs/borderaux.html.twig", [
            'borderaux' => $borderaux,
            'reglements' => $reglements,
            'text' => $text
        ])->getContent();
        $mpdf = new Mpdf([
            'format' => 'A4-L',
            'mode' => 'utf-8',
            'margin_left' => '5',
            'margin_right' => '5',
            ]);
        $mpdf->SetTitle('Reglement De Facture');
        $mpdf->SetHTMLHeader(
            $this->render("facture/pdfs/header_borderaux.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("facture/pdfs/footer_borderaux.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("Borderaux.pdf", "I");
    }
    
    #[Route('/supprimer_borderaux/{bordereau}', name: 'supprimer_borderau')]
    public function supprimerborderau(TBrdpaiement $bordereau)
    {  
        if(!$bordereau){
            return new JsonResponse('Bordereau Introuvable!', 500); 
        }
        $reglements = $bordereau->getReglements();
        if(count($reglements) > 1){
            foreach($reglements as $reglement){
                $reglement->setBordereau(Null);
            }
        }
        $this->em->remove($bordereau);
        $this->em->flush();
        return new JsonResponse('Bordereau Supprimé', 200); 
    }
    
    
    #[Route('/extraction_borderaux/{bordereau}', name: 'extraction_borderaux')]
    public function extraction_borderaux(TBrdpaiement $bordereau)
    {   
        // dd($this->em->getRepository(TBrdpaiement::class)->find(7104));
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ORD');
        $sheet->setCellValue('B1', 'CODE BORDEREAU');
        $sheet->setCellValue('C1', 'ETABLISSEMENT');
        $sheet->setCellValue('D1', 'MODALITE');
        $sheet->setCellValue('E1', 'CODE REGLEMENT');
        $sheet->setCellValue('F1', 'REG D-CREATION');
        $sheet->setCellValue('G1', 'CODE FACTURE');
        $sheet->setCellValue('H1', 'CODE PRE-INSCRIPTION');
        $sheet->setCellValue('I1', 'NOM');
        $sheet->setCellValue('J1', 'PRENOM');
        $sheet->setCellValue('K1', 'BANQUE');
        $sheet->setCellValue('L1', 'REFERENCE');
        $sheet->setCellValue('M1', 'MONTANT');
        $i=2;
        $j=1;
        // $borderaux = $this->em->getRepository(TBrdpaiement::class)->find();

        foreach ($bordereau->getReglements() as $reglement) {
            $sheet->setCellValue('A'.$i, $j);
            $sheet->setCellValue('B'.$i, $bordereau->getCode());
            $sheet->setCellValue('C'.$i, $bordereau->getEtablissement()->getDesignation());
            $sheet->setCellValue('D'.$i, $bordereau->getModalite()->getDesignation());
            $sheet->setCellValue('E'.$i, $reglement->getCode());
            $sheet->setCellValue('F'.$i, $reglement->getDateReglement());
            $sheet->setCellValue('G'.$i, $reglement->getOperation()->getCode());
            $sheet->setCellValue('H'.$i, $reglement->getOperation()->getPreinscription()->getCode());
            $sheet->setCellValue('I'.$i, $reglement->getOperation()->getPreinscription()->getEtudiant()->getNom());
            $sheet->setCellValue('J'.$i, $reglement->getOperation()->getPreinscription()->getEtudiant()->getPrenom());
            $sheet->setCellValue('K'.$i, $reglement->getBanque()->getDesignation());
            $sheet->setCellValue('L'.$i, $reglement->getReference());
            $sheet->setCellValue('M'.$i, $reglement->getMontant());
            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Extraction Borderaux '.$bordereau->getId().'.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
