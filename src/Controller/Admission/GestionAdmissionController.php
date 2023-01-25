<?php

namespace App\Controller\Admission;

use App\Controller\ApiController;
use Mpdf\Mpdf;
use App\Entity\PFrais;
use App\Entity\PDocument;
use App\Entity\POrganisme;
use App\Entity\TAdmission;
use App\Entity\TReglement;
use App\Entity\TOperationcab;
use App\Entity\TOperationdet;
use App\Entity\TAdmissionDocument;
use App\Controller\DatatablesController;
use App\Entity\AcAnnee;
use App\Entity\AcEtablissement;
use App\Entity\AcPromotion;
use App\Entity\PStatut;
use App\Entity\TInscription;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admission/gestion')]

class GestionAdmissionController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        
    }
    #[Route('/', name: 'gestion_admission')]
    public function index(Request $request): Response
    {
     
        //check if user has access to this page
        $operations = ApiController::check($this->getUser(), 'gestion_admission', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        // dd($operations);
        return $this->render('admission/gestion_admission.html.twig', [
            'etablissements' =>  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'gestion_admission_list')]
    public function gestionAdmissionList(Request $request): Response
    {
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 ";   
        // dd($params->all('columns')[0]);

        if (!empty($params->all('columns')[0]['search']['value'])) {
            // dd("in");
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }

        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and form.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and an.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }    
        $columns = array(
            array( 'db' => 'ad.id','dt' => 0),
            array( 'db' => 'ad.code','dt' => 1),
            array( 'db' => 'UPPER(pre.code)','dt' => 2),
            array( 'db' => 'etu.nom','dt' => 3),
            array( 'db' => 'etu.prenom','dt' => 4),
            array( 'db' => 'etab.abreviation','dt' => 5),
            array( 'db' => 'UPPER(form.abreviation)','dt' => 6),
            array( 'db' => 'tab.montant','dt' => 7),
            array( 'db' => 'st.designation','dt' => 8)
        );
        $filtre .= " AND ad.statut_id = 7 ";
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                      
                FROM tadmission ad
                inner join tpreinscription pre on pre.id = ad.preinscription_id
                inner join tetudiant etu on etu.id = pre.etudiant_id
                inner join ac_annee an on an.id = pre.annee_id
                inner join ac_formation form on form.id = an.formation_id              
                inner join ac_etablissement etab on etab.id = form.etablissement_id 
                INNER JOIN pstatut st ON st.id = ad.statut_id
                LEFT JOIN (SELECT adm.id id_admission,SUM(det.montant) montant 
                FROM toperationcab cab 
                INNER JOIN tadmission adm ON adm.preinscription_id = cab.preinscription_id
                INNER JOIN toperationdet det ON cab.id = det.operationcab_id 
                INNER JOIN ac_annee an ON an.id = cab.annee_id 
                GROUP BY adm.id) tab ON tab.id_admission = ad.id 
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
                if($key == 8) {
                    $nestedData[] = count($this->em->getRepository(TAdmission::class)->find($row['id'])->getInscriptions()) > 0 ? 'Inscrit' : 'Non Inscrit';
                    $nestedData[] = $value;
                }
                else if($key > 0) {
                    $nestedData[] = $value;
                }
            }
            if($row['montant'] == '') {
                $cd .= ' etat_bg_nf';
            } else {
                $cd .= ' etat_bg_reg';
            }
            $nestedData["DT_RowId"] = $row['id'];
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
    #[Route('/getdocuments/{admission}', name: 'admission_getdocuments')]
    public function admissionGetdocuments(Request $request, TAdmission $admission): Response
    {
        $documentsExists = $this->em->getRepository(TAdmissionDocument::class)->findBy(['preinscription' => $admission->getPreinscription()]);
        if(count($documentsExists) > 0) {
            $documents = $this->em->getRepository(PDocument::class)->getDocumentDoesNotExistAdmission($admission);
        } else {
          $documents = $this->em->getRepository(PDocument::class)->findAllBy($admission->getPreinscription()->getAnnee()->getFormation()->getEtablissement(), "INSCRIPTION");
        }
        $documentHtml = "";
        $documentExistHtml = "";
        foreach ($documentsExists as $documentsExist) {
            $documentExistHtml .= '
            <li class="ms-elem-selection" id="'.$documentsExist->getDocument()->getId().'" >
                <span> '.$documentsExist->getDocument()->getDesignation().' </span>
            </li>';
        }
        foreach ($documents as $document) {
            $documentHtml .= '
            <li class="ms-elem-selectable" id="'.$document->getId().'">
                <span> '.$document->getDesignation().' </span>
            </li>';
            
        }
        
        return new JsonResponse(['documents' => $documentHtml, 'documentsExists' => $documentExistHtml], 200);
    }
    
    #[Route('/adddocuments', name: 'admission_adddocuments')]
    public function addDocuments(Request $request): Response
    {
        $admission = $this->em->getRepository(TAdmission::class)->find($request->get('idAdmission'));
        $documentAdmission = new TAdmissionDocument();
        $documentAdmission->setPreinscription($admission->getPreinscription());
        $documentAdmission->setDocument(
            $this->em->getRepository(PDocument::class)->find($request->get('idDocument'))
        );
        $this->em->persist($documentAdmission);
        $this->em->flush();
        $documentAdmission->setCode('DIN'.str_pad($documentAdmission->getId(), 8, '0', STR_PAD_LEFT));
        $this->em->flush();
        return new JsonResponse('Bien Enregistre', 200);
    }
    #[Route('/deletedocument', name: 'admission_deletedocument')]
    public function deleteDocument(Request $request): Response
    {
        $admission = $this->em->getRepository(TAdmission::class)->find($request->get('idAdmission'));
        $existDocument = $this->em->getRepository(TAdmissionDocument::class)->findOneBy(['preinscription' => $admission->getPreinscription(), 'document' => $request->get('idDocument')]);
        $this->em->remove($existDocument);
        $this->em->flush();
        return new JsonResponse('Bien Enregistre', 200);
    }
    #[Route('/info/{admission}', name: 'admission_info')]
    public function admissionInfo(Request $request, TAdmission $admission): Response
    {
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

    #[Route('/addfrais/{admission}', name: 'admission_addfrais')]
    public function admissionAddFrais(Request $request, TAdmission $admission): Response
    {
        
        $arrayOfFrais = json_decode($request->get('frais'));
        $preinscription = $admission->getPreinscription();
        $operationcab = $this->em->getRepository(TOperationcab::class)->findOneBy(['preinscription'=>$preinscription,'categorie'=>'admission']);
        
        if ($operationcab != Null) {
            if ($operationcab->getActive() == 0) {
                return new JsonResponse('Facture Cloturée', 500);
            }
        }
        foreach ($arrayOfFrais as $fraisObject) {
            $frais =  $this->em->getRepository(PFrais::class)->find($fraisObject->id);
            $operationDet = new TOperationdet();
            $operationDet->setOperationcab($operationcab);
            $operationDet->setFrais($frais);
            $operationDet->setMontant($fraisObject->montant);
            $operationDet->setIce($fraisObject->ice);
            $operationDet->setCreated(new \DateTime("now"));
            $operationDet->setRemise(0);
            $operationDet->setActive(1);
            $operationDet->setOrganisme($this->em->getRepository(POrganisme::class)->find($fraisObject->organisme_id));
            $this->em->persist($operationDet);
            $this->em->flush();
            $operationDet->setCode(
                "OPD".str_pad($operationDet->getId(), 8, '0', STR_PAD_LEFT)
            );
            $this->em->flush();
        }

        return new JsonResponse($operationcab->getId(), 200);
    }

    #[Route('/facture/{operationcab}', name: 'admission_facture')]
    public function factureAdmission(Request $request, TOperationcab $operationcab)
    {
        $operationdets = $this->em->getRepository(TOperationdet::class)->FindDetGroupByFrais($operationcab);
        $operationdetslist = [];
        foreach ($operationdets as $operationdet) {
            $frais = $operationdet->getFrais();
            $SumByOrg = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFactureAndOrganisme($operationcab,$frais);
            // $SumByOrgPyt = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFactureAndOrganismePayant($operationcab,$frais);
            $SumByPayant = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFactureAndPayant($operationcab,$frais);
            $list['dateOperation'] = $this->em->getRepository(TOperationdet::class)->findOneBy(['operationcab'=>$operationcab,'frais'=>$frais],['created'=>'DESC'])->getCreated()->format('d/m/Y');
            $list['designation'] = $operationdet->getFrais()->getDesignation();
            $list['SumByOrg'] = $SumByOrg;
            // $list['SumByOrgPyt'] = $SumByOrgPyt;
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





        // $reglementTotal = $this->em->getRepository(TReglement::class)->getSumMontantByCodeFacture($operationcab);
        // $operationTotal = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFacture($operationcab);
        // $operationTotal = $operationTotal == Null ? 0 : $operationTotal['total'];
        // $reglementTotal = $reglementTotal == Null ? 0 : $reglementTotal['total'];
        // $total = $operationTotal - $reglementTotal;
        // // dd($reglementTotal, $operationTotal);
        // $html = $this->render("facture/pdfs/facture.html.twig", [
        //     'reglementTotal' => $reglementTotal,
        //     'operationTotal' => $operationTotal,
        //     'operationcab' => $operationcab,
        //     'total' => $total
        // ])->getContent();
        // $mpdf = new Mpdf();
        // $mpdf->SetHTMLHeader(
        //     $this->render("facture/pdfs/header.html.twig")->getContent()
        // );
        // $mpdf->SetHTMLFooter(
        //     $this->render("facture/pdfs/footer.html.twig")->getContent()
        // );
        // $mpdf->WriteHTML($html);
        // $mpdf->Output("facture.pdf", "I");
    }
    #[Route('/getAnneeDisponible/{admission}', name: 'admission_annee_disponible')]
    public function getAnneeDisponible(Request $request, TAdmission $admission): Response
    {
        $annee = date('Y').'/'.date('Y')+1;
        $inscription = $this->em->getRepository(TInscription::class)->getActiveInscriptionByAnnee($admission,$annee);
        if(!$inscription) {
            $limit = 1;
            if ($admission->getPreinscription()->getAnnee()->getFormation()->getEtablissement()->getAbreviation() == 'CFC') {
                $limit = 2;
            }
            $findAnnee = $this->em->getRepository(AcAnnee::class)->findBy([ 'formation' => $admission->getPreinscription()->getAnnee()->getFormation()],['id'=>'DESC'],$limit);
            $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation' => $admission->getPreinscription()->getAnnee()->getFormation()]);
            $promotionHtml = ApiController::dropdown($promotions, "promotion");
            $anneeHtml = ApiController::dropdown($findAnnee, "annee");
            return new JsonResponse(['anneeHtml' => $anneeHtml, 'promotionHtml' => $promotionHtml], 200);
        }

        return new JsonResponse("Etudiant deja inscrit à l'année courant!", 500);
    }
    #[Route('/inscription/{admission}', name: 'admission_inscription')]
    public function inscriptionAction(Request $request, TAdmission $admission)
    {
        $annee = $this->em->getRepository(AcAnnee::class)->find($request->get('annee_inscription'));
        $promotion = $this->em->getRepository(AcPromotion::class)->find($request->get('promotion_inscription'));
        // dd($admission->getPreinscription()->getEtudiant()->getCategoriePreinscription());
        $etudiant = $admission->getPreinscription()->getEtudiant();
        
        if ($etudiant->getNationalite() == 'MOROCCO' || $etudiant->getCategoriePreinscription() == 'NOUVELLE PRE-INSCRIPTION') {            
            if ($promotion->getLimite() != Null) {
                $inss = $this->em->getRepository(TInscription::class)->findBy(['promotion'=>$promotion,'annee'=>$annee,'statut'=>13]);
                if (count($inss) >= $promotion->getLimite()) {
                    return new JsonResponse("La liste est Complete!!", 500);
                }
            }
        }

        $inscription = new TInscription();
        $inscription->setStatut(
            $this->em->getRepository(PStatut::class)->find(13)
        );
        $inscription->setUserCreated($this->getUser());
        $inscription->setAnnee($annee);
        $inscription->setPromotion($promotion);
        $inscription->setAdmission($admission);
        $inscription->setCreated(new \DateTime("now"));
        $this->em->persist($inscription);
        $this->em->flush();
        $inscription->setCode('INS-'. $annee->getFormation()->getEtablissement()->getAbreviation().str_pad($inscription->getId(), 8, '0', STR_PAD_LEFT).'/'.date("Y"));
        $this->em->flush();
        // Add New Facture Inscription
        // dd($operationcabs = $this->em->getRepository(TOperationcab::class)->findBy(['preinscription'=>$admission->getPreinscription()]));
        if (count($admission->getInscriptions()) > 1) {
            $operationcabs = $this->em->getRepository(TOperationcab::class)->findBy(['preinscription'=>$inscription->getAdmission()->getPreinscription()]);
            foreach($operationcabs as $operationcab){
                $operationcab->setActive(0);
            }   
        }
        
        for ($i=0; $i < 2; $i++) { 
            $operationCab = new TOperationcab();
            $operationCab->setPreinscription($inscription->getAdmission()->getPreinscription());
            $operationCab->setUserCreated($this->getUser());
            // A supprimer
            $operationCab->setOrganisme(
                $this->em->getRepository(POrganisme::class)->find(7)
            );
            //
            $operationCab->setAnnee($inscription->getAnnee());
            $i == 0 ? $operationCab->setCategorie('inscription') : $operationCab->setCategorie('hors inscription');
            $operationCab->setCreated(new \DateTime("now"));
            $operationCab->setActive(1);
            $this->em->persist($operationCab);
            $this->em->flush();
            $operationCab->setCode(
                $inscription->getAnnee()->getFormation()->getEtablissement()->getAbreviation()."-FAC".str_pad($operationCab->getId(), 8, '0', STR_PAD_LEFT)."/".date('Y')
            );
            $this->em->flush();
        }
        return new JsonResponse("Bien Enregistre code inscription: " . $inscription->getCode(), 200);
    }
    #[Route('/listadmission/{annee}', name: 'admission_list_admis')]
    public function admissionListAdmis(Request $request, AcAnnee $annee): Response
    {
        $admissions = $this->em->getRepository(TAdmission::class)->getAdmissionByAnnee($annee);
        $html = $this->render("admission/pdfs/list.html.twig", [
            "annee" => $annee,
            "admissions" => $admissions
            ])->getContent();
        $mpdf = new Mpdf();
        $mpdf->SetHTMLHeader(
            $this->render("admission/pdfs/header.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("admission/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("facture.pdf", "I");
    }

    #[Route('/attestation/{admission}', name: 'attestation_admisison')]
    public function attestationAdmisison(Request $request, TAdmission $admission): Response
    {
        $preinscription = $admission->getPreinscription();
        $html = $this->render("attestaion/pdfs/admission.html.twig", [
            'preinscription' => $preinscription,
            'annee' => $preinscription->getAnnee(),
            'etablissement' => $preinscription->getAnnee()->getFormation()->getEtablissement(),
            'formation' => $preinscription->getAnnee()->getFormation(),
            'etudiant' => $preinscription->getEtudiant(),
        ])->getContent();
        $mpdf = new Mpdf();
        $mpdf->SetHTMLHeader(
            $this->render("attestaion/pdfs/header.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("attestaion/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("attestaion.pdf", "I");
    }

    #[Route('/print_documents_admission/{admission}', name: 'print_documents_admission')]
    public function print_documents_admission(TAdmission $admission)
    {
        // dd($preinscription->getDocuments()[0]);
        // dd($preinscription->getNature());
        $documentsExists = $this->em->getRepository(TAdmissionDocument::class)->findBy(['preinscription' => $admission->getPreinscription()]);
        $admdocs = [];
        foreach ($documentsExists as $documentsExist) {
            array_push($admdocs,$documentsExist->getDocument());
        }
        // dd($admdocs);
        $documents = $this->em->getRepository(PDocument::class)->findBy([
            'attribution' => 'INSCRIPTION',
            'etablissement' => $admission->getPreinscription()->getAnnee()->getFormation()->getEtablissement(),
            'active' => 1,
        ]);
        // dd($documents);
        $html = $this->render("admission/pdfs/documents_admission.html.twig", [
            'preinscription' => $admission->getPreinscription(),
            'documents' => $documents,
            'admdocs' => $admdocs
        ])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '12',
            'margin_right' => '12',
        ]);
        $mpdf->SetTitle('Documents de Pré-Inscription');
        $mpdf->SetHTMLHeader(
            $this->render("attestaion/pdfs/header.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("attestaion/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("attestaion.pdf", "I");
    } 
    
}
