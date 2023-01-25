<?php

namespace App\Controller\Inscription;

use Mpdf\Mpdf;
use App\Entity\PFrais;
use App\Entity\PStatut;
use App\Entity\POrganisme;
use App\Entity\TReglement;
use App\Entity\TInscription;
use App\Entity\TOperationcab;
use App\Entity\TOperationdet;
use App\Entity\AcEtablissement;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\TAdmission;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/inscription/gestion')]
class GestionInscriptionController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'gestion_inscription')]
    public function index(Request $request): Response
    {
         //check if user has access to this page
        $operations = ApiController::check($this->getUser(), 'gestion_inscription', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");

        }
        return $this->render('inscription/gestion_inscription.html.twig', [
            'etablissements' =>  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]),
            'operations' => $operations
        ]);
    }
    
    #[Route('/list', name: 'gestion_inscription_list')]
    public function gestionInscriptionList(Request $request): Response
    {
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 and pre.inscription_valide = 1 ";   
        // dd($params->all('columns')[0]);
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            // dd("in");
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        } 
        if (!empty($params->all('columns')[1]['search']['value'])) {
                $filtre .= " and form.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and prom.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[3]['search']['value'])) {
            $filtre .= " and an.id = '" . $params->all('columns')[3]['search']['value'] . "' ";
        }    
        $columns = array(
            array( 'db' => 'ins.id','dt' => 0),
            array( 'db' => 'ins.code','dt' => 1),
            array( 'db' => 'etu.nom','dt' => 2),
            array( 'db' => 'etu.prenom','dt' => 3),
            array( 'db' => 'etu.cne','dt' => 4),
            array( 'db' => 'etu.cin','dt' => 5),
            array( 'db' => 'etab.abreviation','dt' => 6),
            array( 'db' => 'UPPER(form.abreviation)','dt' => 7),
            array( 'db' => 'UPPER(prom.designation)','dt' => 8),
            array( 'db' => 'LOWER(an.designation)','dt' => 9),
            array( 'db' => 'st.designation','dt' => 10),
           
            
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM tinscription ins
        inner join tadmission ad on ad.id = ins.admission_id
        inner join tpreinscription pre on pre.id = ad.preinscription_id
        inner join tetudiant etu on etu.id = pre.etudiant_id
        inner join ac_annee an on an.id = ins.annee_id
        inner join ac_formation form on form.id = an.formation_id              
        inner join ac_etablissement etab on etab.id = form.etablissement_id 
        INNER JOIN pstatut st ON st.id = ins.statut_id
        inner join ac_promotion prom on prom.id = ins.promotion_id
        $filtre "
        ;
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $this->em->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());
        // dd($sql);
        $my_columns = DatatablesController::Pluck($columns, 'db');
            
        // search 
        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }
        $changed_column = $params->all('order')[0]['column'] > 0 ? $params->all('order')[0]['column'] - 1 : 0;
        $sqlRequest .= " ORDER BY " .DatatablesController::Pluck($columns, 'db')[$changed_column] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";
        // $sqlRequest .= DatatablesController::Order($request, $columns);
        
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        
        
        $data = array();
        // dd($result);
        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            $nestedData[] = "<input type ='checkbox' class='check_admissible' id ='$cd' >";
            $nestedData[] = $cd;
            // dd($row);
            
            foreach (array_values($row) as $key => $value) {
                if($key > 0) {
                    $nestedData[] = $value;
                }
            }
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = $cd;
            $data[] = $nestedData;
            $i++;
        }
        // dd($data);
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        // die;
        return new Response(json_encode($json_data));
    }
    #[Route('/getstatut/{inscription}', name: 'gestion_statut')]
    public function inscriptionStatut(TInscription $inscription): Response
    {
        $status = $this->em->getRepository(PStatut::class)->findBy(['table0' => 'Inscription']);
        $statutsHtml = ApiController::dropDownSelected($status, "statut", $inscription->getStatut());

        return new JsonResponse($statutsHtml);
    }
    #[Route('/updatestatut/{inscription}', name: 'gestion_statut_update')]
    public function inscriptionStatutUpdate(Request $request, TInscription $inscription): Response
    {
        if ($request->get('statut_inscription') == "") {
            return new JsonResponse("Merci de choisir un statut!", 500);
        }
        $inscription->setStatut(
            $this->em->getRepository(PStatut::class)->find($request->get("statut_inscription"))
        );
        $this->em->flush();
        return new JsonResponse("Bien Enregistre", 200);
    }

    #[Route('/frais/{inscription}', name: 'getFraisByInscription')]
    public function getFraisByInscription(TInscription $inscription): Response
    {   
        $operationcab = $this->em->getRepository(TOperationcab::class)->findOneBy(['preinscription'=>$inscription->getAdmission()->getPreinscription(),'categorie'=>'inscription']);
        // dd($operationcab);
        if (!$operationcab) {
            return new JsonResponse('Facture Introuvable!', 500); 
        }
        $frais = $this->em->getRepository(PFrais::class)->findBy(["formation" => $inscription->getAnnee()->getFormation(), "categorie" => "inscription",'active'=>1]);
        $data = ApiController::dropdownData($frais,'frais');
        return new JsonResponse(['list' => $data, 'codefacture' => $operationcab->getCode()], 200); 
    }
    #[Route('/info/{inscription}', name: 'getInformationByInscription')]
    public function getFraisByFormation(TInscription $inscription): Response
    {   
        $admission = $inscription->getAdmission();
        $etudiant = $admission->getPreinscription()->getEtudiant();
        $natutre = $admission->getPreinscription()->getNature();
        $annee = $admission->getPreinscription()->getAnnee();
        $formation =$annee->getFormation();
        $etablissement=$formation->getEtablissement();
        $donnee_frais = "<p><span>Etablissement</span> : ".$etablissement->getDesignation()."</p>
                        <p><span>Formation</span> : ".$formation->getDesignation()."</p>
                        <p><span>Categorie</span> : ".$natutre->getDesignation()."</p>
                        <p><span>Nom</span> : ".$etudiant->getNom()."</p>
                        <p><span>Prenom</span> : ".$etudiant->getPrenom()."</p>
                        <p><span>Cin</span> : ".$etudiant->getCin()."</p>
                        <p><span>Cne</span> : ".$etudiant->getCne()."</p>";
        return new JsonResponse($donnee_frais, 200);       
    }
    #[Route('/addfrais/{inscription}', name: 'inscription_addfrais')]
    public function inscriptionAddFrais(Request $request, TInscription $inscription): Response
    {
        $arrayOfFrais = json_decode($request->get('frais'));
        $preinscription = $inscription->getAdmission()->getPreinscription();
        $operationCab = $this->em->getRepository(TOperationcab::class)->findOneBy(['preinscription'=>$preinscription,'categorie'=>'inscription']);
        if ($operationCab->getActive() == 0) {
            return new JsonResponse('Facture Cloturée', 500);
        }
        foreach ($arrayOfFrais as $fraisObject) {
            $frais =  $this->em->getRepository(PFrais::class)->find($fraisObject->id);
            $operationDet = new TOperationdet();
            $operationDet->setOperationcab($operationCab);
            $operationDet->setFrais($frais);
            $operationDet->setMontant($fraisObject->montant);
            $operationDet->setIce($fraisObject->ice);
            $operationDet->setCreated(new \DateTime("now"));
            $operationDet->setUpdated(new \DateTime("now"));
            $operationDet->setOrganisme($this->em->getRepository(POrganisme::class)->find($fraisObject->organisme_id));
            $operationDet->setRemise(0);
            $operationDet->setActive(1);
            $this->em->persist($operationDet);
            $this->em->flush();
            $operationDet->setCode(
                "OPD".str_pad($operationDet->getId(), 8, '0', STR_PAD_LEFT)
            );
            $this->em->flush();
        }

        return new JsonResponse($operationCab->getId(), 200);
    }
    #[Route('/facture/{operationcab}', name: 'inscription_facture')]
    public function factureInscription(Request $request, TOperationcab $operationcab)
    {
        $operationdets = $this->em->getRepository(TOperationdet::class)->FindDetGroupByFrais($operationcab);
        $operationdetslist = [];
        foreach ($operationdets as $operationdet) {
            $frais = $operationdet->getFrais();
            $SumByOrg = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFactureAndOrganisme($operationcab,$frais);
            $SumByOrgPyt = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFactureAndOrganismePayant($operationcab,$frais);
            $SumByPayant = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFactureAndPayant($operationcab,$frais);
            $list['dateOperation'] = $this->em->getRepository(TOperationdet::class)->findOneBy(['operationcab'=>$operationcab,'frais'=>$frais],['created'=>'DESC'])->getCreated()->format('d/m/Y');
            $list['designation'] = $operationdet->getFrais()->getDesignation();
            $list['SumByOrg'] = $SumByOrg;
            $list['SumByOrgPyt'] = $SumByOrgPyt;
            $list['SumByPayant'] = $SumByPayant;
            $list['total'] = $SumByPayant + $SumByOrg;
            array_push($operationdetslist,$list);
        }
        $inscription = $this->em->getRepository(TInscription::class)->findOneBy([
            'admission'=>$this->em->getRepository(TAdmission::class)->findBy([
                'preinscription'=>$operationcab->getPreinscription()]),
            'annee' => $operationcab->getAnnee()]);
        $promotion = $inscription == NULL ? "" : $inscription->getPromotion()->getDesignation();
        
        $reglementOrg = $this->em->getRepository(TReglement::class)->getReglementSumMontantByCodeFactureByOrganisme($operationcab)['total'];
        $reglementPyt = $this->em->getRepository(TReglement::class)->getReglementSumMontantByCodeFactureByPayant($operationcab)['total'];
        
        $html = $this->render("facture/pdfs/facture_facture.html.twig", [
            'reglementOrg' => $reglementOrg,
            'reglementPyt' => $reglementPyt,
            'operationcab' => $operationcab,
            'promotion' => $promotion,
            'operationdets' => $operationdetslist
        ])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_top' => 5,
        ]);        
        $mpdf->SetTitle('Facture');
        $mpdf->SetHTMLFooter(
            $this->render("facture/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML($html);
        $mpdf->Output("facture.pdf", "I");
    }
    
    #[Route('/extraction_ins', name: 'extraction_ins')]
    public function extraction_ins()
    {   
        // echo('Cration en cours!!');die;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ORD');
        $sheet->setCellValue('B1', 'CODE CONDIDAT');
        $sheet->setCellValue('C1', 'CODE PREINSCRIPTION');
        $sheet->setCellValue('D1', 'CODE ADMISSION');
        $sheet->setCellValue('E1', 'ID INSCRIPTION');
        $sheet->setCellValue('F1', 'CODE INSCRIPTION');
        $sheet->setCellValue('G1', 'STATUT');
        $sheet->setCellValue('H1', 'NOM');
        $sheet->setCellValue('I1', 'PRENOM');
        $sheet->setCellValue('J1', 'SEXE');
        $sheet->setCellValue('K1', 'DATE NAISSANCE');
        $sheet->setCellValue('L1', 'CIN');
        $sheet->setCellValue('M1', 'VILLE');
        $sheet->setCellValue('N1', 'TEL CANDIDAT');
        $sheet->setCellValue('O1', 'MAIL CANDIDAT');
        $sheet->setCellValue('P1', 'ETABLISSEMENT');
        $sheet->setCellValue('Q1', 'FORMATION');
        $sheet->setCellValue('R1', "NIVEAU D'ETUDES");
        $sheet->setCellValue('S1', 'TYPE DE BAC');
        $sheet->setCellValue('T1', 'ANNEE BAC');
        $sheet->setCellValue('U1', 'MOYENNE GENERALE');
        $sheet->setCellValue('V1', 'MOYENNE NATIONALE');
        $sheet->setCellValue('W1', 'MOYENNE REGIONALE');
        $sheet->setCellValue('Y1', 'D-INSCRIPTION');
        $sheet->setCellValue('Z1', 'STATUT');
        // $sheet->setCellValue('U1', 'N°FACTURE');
        // $sheet->setCellValue('V1', 'MONTANT FACTURE');
        // $sheet->setCellValue('W1', 'MONTANT REGLE');
        // $sheet->setCellValue('X1', 'RESTE');
        // $sheet->setCellValue('Y1', 'TYPE REGLEMENT');
        // $sheet->setCellValue('Z1', 'REFERENCE REGLEMENT');
        // $sheet->setCellValue('AA1', 'DATE FACTURE');
        // $sheet->setCellValue('AB1', 'DATE REGLEMENT');
        $i=2;
        $j=1;
        $current_year = date('m') > 7 ? $current_year = date('Y').'/'.date('Y')+1 : $current_year = date('Y') - 1 .'/' .date('Y');
        // dd($current_year);
        // $current_year = "2022/2023";
        $inscriptions = $this->em->getRepository(TInscription::class)->getActiveInscriptionByCurrentAnnee($current_year);
        // dd($inscriptions);
        foreach ($inscriptions as $inscription) {
            $sheet->setCellValue('A'.$i, $j);
            $sheet->setCellValue('B'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getCode());
            $sheet->setCellValue('C'.$i, $inscription->getAdmission()->getPreinscription()->getCode());
            $sheet->setCellValue('D'.$i, $inscription->getAdmission()->getCode());
            $sheet->setCellValue('E'.$i, $inscription->getId());
            $sheet->setCellValue('F'.$i, $inscription->getCode());
            if ($inscription->getAdmission()->getPreinscription()->getNature() != null) {
                $sheet->setCellValue('G'.$i, $inscription->getAdmission()->getPreinscription()->getNature()->getDesignation());
            }
            $sheet->setCellValue('H'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getNom());
            $sheet->setCellValue('I'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getPrenom());
            $sheet->setCellValue('J'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getSexe());
            $sheet->setCellValue('K'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getDateNaissance());
            $sheet->setCellValue('L'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getCin());
            $sheet->setCellValue('M'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getVille());
            $sheet->setCellValue('N'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getTel1());
            $sheet->setCellValue('O'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getMail1());
            $sheet->setCellValue('P'.$i, $inscription->getAnnee()->getFormation()->getEtablissement()->getDesignation());
            $sheet->setCellValue('Q'.$i, $inscription->getAnnee()->getFormation()->getDesignation());
            $sheet->setCellValue('R'.$i, $inscription->getPromotion()->getDesignation());
            $sheet->setCellValue('S'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getTypeBac() == Null ? "" : $inscription->getAdmission()->getPreinscription()->getEtudiant()->getTypeBac()->getDesignation());
            $sheet->setCellValue('T'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getAnneeBac());
            $sheet->setCellValue('U'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getMoyenneBac());
            $sheet->setCellValue('V'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getMoyenNational());
            $sheet->setCellValue('W'.$i, $inscription->getAdmission()->getPreinscription()->getEtudiant()->getMoyenRegional());
            $sheet->setCellValue('Y'.$i, $inscription->getCreated());
            $sheet->setCellValue('Z'.$i, $inscription->getStatut()->GetDesignation());

            // $facture = $this->em->getRepository(TOperationcab::class)->findOneBy(['categorie'=>'inscription','preinscription'=>$inscription->getAdmission()->getPreinscription(),'active'=>1]);
            // if ($facture) {
            //     $sheet->setCellValue('U'.$i, $facture->getCode());
            //     $sommefacture = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFacture($facture);
            //     $sommefacture = $sommefacture == Null ? 0 : $sommefacture['total'];
            //     $sheet->setCellValue('V'.$i, $sommefacture);
            //     $sommereglement = $this->em->getRepository(TReglement::class)->getSumMontantByCodeFacture($facture);
            //     $sommereglement = $sommereglement == Null ? 0 : $sommereglement['total'];
            //     $sheet->setCellValue('W'.$i, $sommereglement);
            //     $reste = $sommefacture - $sommereglement;
            //     $sheet->setCellValue('X'.$i, $reste);
            //     $reglement = $this->em->getRepository(TReglement::class)->findOneBy(['operation'=>$facture],['id'=>'DESC']);
            //     if ($reglement) {
            //         $sheet->setCellValue('Y'.$i, $reglement->getPaiement()->getDesignation());
            //         $sheet->setCellValue('Z'.$i, $reglement->getCode());
            //     }
            //     $sheet->setCellValue('AA'.$i, $facture->getCreated());
            //     if ($reglement) {
            //         $sheet->setCellValue('AB'.$i, $reglement->getCreated());
            //     }
            // }
            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Extraction Inscription.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

}
