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
use App\Entity\AcAnnee;
use App\Entity\AcFormation;
use App\Entity\TPreinscription;
use App\Entity\TInscription;
use App\Entity\TAdmission;
use App\Entity\AcPromotion;
use App\Entity\POrganisme;
use App\Entity\XBanque;
use App\Entity\PFrais;
use App\Entity\TReglement;
use App\Entity\TOperationcab;
use App\Entity\TOperationdet;
use App\Entity\XModalites;
use App\Entity\TBrdpaiement;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use phpDocumentor\Reflection\Types\Null_;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\nuts;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/facture/reglements')]
class GestionReglementsController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        
    }
    #[Route('/', name: 'gestion_reglements')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'gestion_reglements', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etablissements = $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $banques = $this->em->getRepository(XBanque::class)->findAll();
        $paiements = $this->em->getRepository(XModalites::class)->findBy(['active'=>1]);
        $bordereaux = $this->em->getRepository(TBrdpaiement::class)->findAll();
        
        return $this->render('facture/gestion_reglement.html.twig', [
            'etablissements' => $etablissements,
            'paiements' => $paiements,
            'bordereaux' => $bordereaux,
            'operations' => $operations,
            'banques' => $banques,
        ]);
    }
    
    #[Route('/list', name: 'list_facture_reglement')]
    public function list_facture_reglement(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where 1=1 and (reg.annuler != 1 or reg.annuler is null) ";
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and frm.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and pae.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[3]['search']['value'])) {
            if ($params->all('columns')[3]['search']['value'] == 'non') {
                $filtre .= " and (brd.code is NULL OR brd.code = 'NULL' OR brd.code = '' OR brd.code = ' ')";
            } else {
                $filtre .= " and (brd.code is NOT NULL OR brd.code <> 'NULL' OR brd.code <> '' OR brd.code <> ' '  )";
            }
        }
        $columns = array(
            array( 'db' => 'reg.id','dt' => 0 ),
            array( 'db' => 'pre.code','dt' => 1),
            array( 'db' => 'lower(oprcab.code)','dt' => 2),
            array( 'db' => 'Upper(reg.code)','dt' => 3),
            array( 'db' => 'etu.nom','dt' => 4),
            array( 'db' => 'etu.prenom','dt' => 5),
            array( 'db' => 'etu.cin','dt' => 6),
            array( 'db' => 'upper(frm.abreviation)','dt' => 7),
            array( 'db' => 'reg.montant','dt' => 8),
            array( 'db' => 'reg.reference','dt' => 9),
            array( 'db' => 'DATE_FORMAT(reg.date_reglement,"%Y-%m-%d")','dt' => 10),
            array( 'db' => 'pae.designation','dt' => 11),
            array( 'db' => 'upper(ban.designation)','dt' => 12),
            array( 'db' => 'lower(brd.code)','dt' => 13),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM treglement reg 
        INNER JOIN toperationcab oprcab ON oprcab.id = reg.operation_id
        INNER JOIN tpreinscription pre ON pre.id = oprcab.preinscription_id
        INNER JOIN tetudiant etu ON etu.id = pre.etudiant_id
        INNER JOIN ac_annee an ON an.id = oprcab.annee_id
        LEFT JOIN ac_formation frm ON frm.id = an.formation_id
        LEFT JOIN ac_etablissement etab ON etab.id = frm.etablissement_id
        left join  xmodalites pae on pae.id = reg.paiement_id 
        left join  xbanque ban on ban.id = reg.banque_id
        left join  tbrdpaiement brd on brd.id = reg.bordereau_id $filtre ";
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
                    $check = $this->em->getRepository(TReglement::class)->find($value);
                    if ((!empty($check->getBordereau())) OR ( $check->getImpayer() == '1')) {
                        $checked = " class='check_reg' disabled checked";
                    }
                    $value = '<input id="check" type="checkbox" data-id='.$value.' '.$checked.'/>';
                }
                $nestedData[] = $value;
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
    
    #[Route('/reglementprint/{reglement}', name: 'imprimer_reglement_reglement')]
    public function imprimer_reglement_reglement(TReglement $reglement)
    {
        $operationcab = $reglement->getOperation();
        $reglementTotal = $this->em->getRepository(TReglement::class)->getSumMontantByCodeFacture($operationcab);
        $operationTotal = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFacture($operationcab);
        $inscription = $this->em->getRepository(TInscription::class)->findOneBy([
            'admission'=>$this->em->getRepository(TAdmission::class)->findBy([
                'preinscription'=>$operationcab->getPreinscription()])]);
        $promotion = $inscription == NULL ? "" : $inscription->getPromotion()->getDesignation();
        $inscription = $inscription == NULL ? "" : $inscription->getCode();
        $html = "";
        for ($i=0; $i < 3; $i++) { 
            $html .= $this->render("facture/pdfs/facture_reglement.html.twig", [
                    'reglementTotal' => $reglementTotal,
                    'operationTotal' => $operationTotal,
                    'operationcab' => $operationcab,
                    'reglement' => $reglement,
                    'inscription' => $inscription,
                    'promotion' => $promotion,
            ])->getContent();
        }
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 
            'format' => [250, 350],
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 3,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output("Reglement-".$reglement->getCode().".pdf", "I");
    }
    
    #[Route('/borderaux/{formation}/{paiement}', name: 'reglement_borderaux')]
    public function reglement_borderaux(AcFormation $formation,XModalites $paiement,Request $request): Response
    {   
        $ids = json_decode($request->get('ids_reglement'));
        $etablissement = $formation->getEtablissement();
        $modalite = $this->em->getRepository(XModalites::class)->find($paiement);
        $borderaux = new TBrdpaiement();
        $borderaux->setModalite($this->em->getRepository(XModalites::class)->find($paiement));
        $borderaux->setEtablissement($etablissement);
        // $borderaux->setMontant(Null);
        $borderaux->setCreated(new DateTime('now'));
        $borderaux->setUserCreated($this->getUser());
        $this->em->persist($borderaux);
        $this->em->flush();
        $total = 0;
        foreach ($ids as $id) {
            $reglement = $this->em->getRepository(TReglement::class)->find($id);
            if ($reglement->getBordereau() == null) {
                $total = $total + $reglement->getMontant();
                if ($reglement->getPaiement()->getId() == $paiement->getId()) {
                    $reglement->setBordereau($borderaux);
                    $this->em->flush();
                }
            }
        }
        $borderaux->setCode($etablissement->getAbreviation().'-BRD'.str_pad($borderaux->getId(), 6, '0', STR_PAD_LEFT).'/'.date('Y'));
        $borderaux->setMontant($total);
        $this->em->flush();
        return new Response($borderaux->getId());
    }
    
    #[Route('/printborderaux/{borderaux}', name: 'printborderaux')]
    public function printborderaux(Request $request,TBrdpaiement $borderaux)
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
    
    #[Route('/creanceprint', name: 'creanceprint')]
    public function creanceprint(Request $request)
    {  
        $creances = $this->em->getRepository(TInscription::class)->getCreance();
        $html = $this->render("facture/pdfs/creance.html.twig", [
            'creances' => $creances,
        ])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '5',
            'margin_right' => '5',
            'margin_top' => '30',
        ]);
        
        $mpdf->SetHTMLHeader(
            $this->render("facture/pdfs/header_creance.html.twig")->getContent()
        );
        $mpdf->SetTitle('Creance');
        $mpdf->WriteHTML($html);
        $mpdf->Output("Creance.pdf", "I");

    }
    
    #[Route('/annuler_reglement/{reglement}', name: 'annuler_reglement')]
    public function annuler_reglement(Request $request,TReglement $reglement)
    {  
        // dd($request->get('motif_annuler'));
        if ($reglement) {
            $reglement->setAnnuler(1);
            $reglement->setAnnulerMotif($request->get('motif_annuler'));
            $this->em->flush();
            return new JsonResponse('Reglement Bien Annuler',200);
        }
    }
    
    #[Route('/getReglementInfos/{reglement}', name: 'getReglementInfos')]
    public function getReglementInfos(TReglement $reglement)
    {  
        // dd($reglement->getDateReglement()->format('d/m/Y'));
        $banques = $this->em->getRepository(XBanque::class)->findAll();
        $paiements = $this->em->getRepository(XModalites::class)->findAll();
        $html = $this->render('facture/pages/edit_reglement.html.twig', [
            'paiements' => $paiements,
            'banques' => $banques,
            'reglement' => $reglement
        ])->getContent();
        // dd($html);
        return new JsonResponse($html, 200); 
    }
    
    #[Route('/modifier_reglement/{id}', name: 'modifier_reglement')]
    public function ajouter_reglement(Request $request,TReglement $reglement): Response
    { 
        if (empty($request->get('d_reglement')) || $request->get('montant') == ""  || empty($request->get('banque')) ||
        empty($request->get('paiement')) ||  empty($request->get('reference')) ) {
            return new JsonResponse('Veuillez renseigner tous les champs!', 500);
        }elseif ($request->get('montant') == 0) {
            return new JsonResponse('Le montant ne peut pas étre égale à 0', 500);
        }
        $reglement->setUpdated(new DateTime('now'));
        $reglement->setMontant($request->get('montant'));
        $reglement->setMProvisoir($request->get('montant_provisoir'));
        $reglement->setMDevis($request->get('montant_devis'));
        $reglement->setBanque($request->get('banque') == "" ? Null : $this->em->getRepository(XBanque::class)->find($request->get('banque')));
        $reglement->setPaiement($this->em->getRepository(XModalites::class)->find($request->get('paiement')));
        $reglement->setDateReglement(new DateTime($request->get('d_reglement')));
        $reglement->setReference($request->get('reference'));
        $reglement->setPayant($request->get('organisme'));
        $reglement->setUserUpdated($this->getUser());
        $this->em->flush();
        return new JsonResponse('Reglement bien modifier', 200);        
    }
    
    #[Route('/extraction_reglement', name: 'extraction_reglement')]
    public function extraction_reglement()
    {   
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ORD');
        $sheet->setCellValue('B1', 'CODE CONDIDAT');
        $sheet->setCellValue('C1', 'CODE PRE-INSCRIPTION');
        $sheet->setCellValue('D1', 'CODE FACTURE');
        $sheet->setCellValue('E1', 'ANNEE UNIVERSITAIRE');
        $sheet->setCellValue('F1', 'NOM');
        $sheet->setCellValue('G1', 'PRENOM');
        $sheet->setCellValue('H1', 'NATIONALITE');
        $sheet->setCellValue('I1', 'ETABLISSEMENT');
        $sheet->setCellValue('J1', 'FORMATION');
        $sheet->setCellValue('K1', 'CODE REGLEMENT');
        $sheet->setCellValue('L1', 'MT REGLE');

        $sheet->setCellValue('M1', 'MT PROVISOIR');
        $sheet->setCellValue('N1', 'MT EN DEVISE');
        
        $sheet->setCellValue('O1', 'REFERENCE DOC');
        $sheet->setCellValue('P1', 'MODE PAIEMENT');
        $sheet->setCellValue('Q1', 'DATE REGLEMENT');
        $sheet->setCellValue('R1', 'D-CREATION REGLEMENT');
        $sheet->setCellValue('S1', 'U-Created');
        $sheet->setCellValue('T1', 'U-Updated');
        $sheet->setCellValue('U1', 'N° BRD');
        $i=2;
        $j=1;
        $currentyear = date('m') > 7 ? $current_year = date('Y').'/'.date('Y')+1 : $current_year = date('Y') - 1 .'/' .date('Y');
        
        // $currentyear = '2022/2023';
        $reglements = $this->em->getRepository(TReglement::class)->getReglementsByCurrentYear($currentyear);
        // dd($reglements);
        foreach ($reglements as $reglement) {
            $sheet->setCellValue('A'.$i, $j);
            $sheet->setCellValue('B'.$i, $reglement['code_etu']);
            $sheet->setCellValue('C'.$i, $reglement['code_preins']);
            $sheet->setCellValue('D'.$i, $reglement['code_facture']);
            $sheet->setCellValue('E'.$i, $reglement['annee']);
            $sheet->setCellValue('F'.$i, $reglement['nom']);
            $sheet->setCellValue('G'.$i, $reglement['prenom']);
            $sheet->setCellValue('H'.$i, $reglement['nationalite']);
            $sheet->setCellValue('I'.$i, $reglement['etablissement']);
            $sheet->setCellValue('J'.$i, $reglement['formation']);
            $sheet->setCellValue('K'.$i, $reglement['code_reglement']);

            $sheet->setCellValue('L'.$i, $reglement['montant_regle']);
            $sheet->setCellValue('M'.$i, $reglement['montant_provisoir']);
            $sheet->setCellValue('N'.$i, $reglement['montant_devis']);

            $sheet->setCellValue('O'.$i, $reglement['reference']);
            $sheet->setCellValue('P'.$i, $reglement['mode_paiement']);

            if ($reglement['date_reglement'] != null) {
                $sheet->setCellValue('Q'.$i, $reglement['date_reglement']->format('d-m-Y'));
            }
            if ($reglement['created'] != null) {
                $sheet->setCellValue('R'.$i, $reglement['created']->format('d-m-Y'));
            }
            $sheet->setCellValue('S'.$i, $reglement['u_created']);
            $sheet->setCellValue('T'.$i, $reglement['u_updated']);
            $sheet->setCellValue('U'.$i, $reglement['num_brd']);
            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Extraction Reglement.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    
}
