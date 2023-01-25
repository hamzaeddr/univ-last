<?php

namespace App\Controller\Preinscription;

use DateTime;
use Mpdf\Mpdf;
use App\Entity\PFrais;
use App\Entity\PStatut;
use App\Entity\POrganisme;
use App\Entity\TOperation;
use App\Entity\TOperationcab;
use App\Entity\TReglement;
use App\Entity\AcEtablissement;
use App\Entity\TOperationdet;
use App\Entity\PDocument;
use App\Entity\TPreinscription;
use App\Entity\NatureDemande;
use App\Entity\User;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\PSituation;
use App\Entity\TAdmission;
use App\Entity\TInscription;
use App\Entity\XAcademie;
use App\Entity\XFiliere;
use App\Entity\XLangue;
use App\Entity\XTypeBac;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/preinscription/gestion')]
class GestionPreinscriptionController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'gestion_preinscription')]
    public function gestion_preinscription(Request $request): Response
    {   
        $operations = ApiController::check($this->getUser(), 'gestion_preinscription', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $natures = $this->em->getRepository(NatureDemande::class)->findBy(['active'=>1]);
        return $this->render('preinscription/gestion_preinscription.html.twig',[
            'etablissements' => $etbalissements,
            'natures' => $natures,
            'operations' => $operations
        ]);
    }
    #[Route('/list/gestion_preinscription', name: 'list/gestion_preinscription')]
    public function list_gestion_preinscription(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1=1 AND inscription_valide = 1 ";
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }

        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and form.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }    
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and nat.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }  

        $columns = array(
            array( 'db' => 'pre.id','dt' => 0 ),
            array( 'db' => 'pre.code','dt' => 1),
            array( 'db' => 'etu.nom','dt' => 2),
            array( 'db' => 'etu.prenom','dt' => 3),
            array( 'db' => 'etu.cin','dt' => 4),
            array( 'db' => 'etab.abreviation','dt' => 5),
            array( 'db' => 'UPPER(form.abreviation)','dt' => 6),
            array( 'db' => 'UPPER(nat.designation)','dt' => 7),
            array( 'db' => 'tbac.designation','dt' => 8),
            array( 'db' => 'etu.moyenne_bac','dt' => 9),
            array( 'db' => 'UPPER(stat.designation)','dt' => 10),
            array( 'db' => 'nbr.nbrIns','dt' => 11),
            array( 'db' => 'DATE_FORMAT(pre.created,"%Y-%m-%d")','dt' => 12),
        );
        // SELECT pre.code , etu.nom , etu.prenom , frm.abreviation as for_abreviation , etab.abreviation as etab_abreviation , nat.designation as categorie, tbac.designation as typdes, etu.moyenne_bac as note , DATE_FORMAT(pre.created,'%d/%m/%Y') as date_creation,stat.code 
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM `tpreinscription` pre 
        inner join tetudiant etu on etu.id = pre.etudiant_id
        inner join ac_annee an on an.id = pre.annee_id
        inner join ac_formation form on form.id = an.formation_id
        inner join ac_etablissement etab on etab.id = form.etablissement_id
        left join xtype_bac tbac on tbac.id = etu.type_bac_id 
        left join nature_demande nat on nat.id = pre.nature_id 
        left join pstatut stat on stat.id = pre.statut_id
        LEFT JOIN (SELECT etudiant_id,COUNT(code) AS nbrIns FROM tpreinscription WHERE etudiant_id IS NOT NULL GROUP BY etudiant_id ) nbr ON nbr.etudiant_id = pre.etudiant_id 
         $filtre ";
        // $sql .= "";
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
        
        $changed_column = $params->all('order')[0]['column'] > 0 ? $params->all('order')[0]['column'] - 1 : 0;
        $sqlRequest .= " ORDER BY " .DatatablesController::Pluck($columns, 'db')[$changed_column] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";
        // $sqlRequest .= DatatablesController::Order($request, $columns);
        
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();


        $data = [];
        
        $i = 1;
        foreach ($result as $key => $row) {
            // dump($row);
            $nestedData = [];
            $cd = $row['id'];
            // $nestedData[] = $cd;
            $nestedData[] = "<input type ='checkbox' class='check_preins' id ='$cd' >";
            $k = 0;
            $etat_bg="";
            foreach (array_values($row) as $key => $value) {
                if($k == 10) {
                    $sqls="SELECT (CASE WHEN EXISTS (SELECT cab.code FROM toperationcab cab INNER JOIN treglement reg ON reg.operation_id = cab.id WHERE cab.preinscription_id = ".$row['id'].") THEN 'Reglé' WHEN EXISTS (SELECT cab2.code FROM toperationcab cab2 LEFT JOIN treglement reg2 ON reg2.operation_id = cab2.id WHERE cab2.preinscription_id = ".$row['id']." ANd reg2.operation_id IS NULL) THEN 'Facturé' ELSE 'N.Facturé' END ) AS facture";
                    $stmts = $this->em->getConnection()->prepare($sqls);
                    $resultSets = $stmts->executeQuery();
                    $etat = $resultSets->fetchAll();
                    if ($etat[0]['facture'] === 'N.Facturé') {
                        $etat_bg = 'etat_bg_nf';
                    }elseif ($etat[0]['facture'] === 'Reglé') {
                        $etat_bg = 'etat_bg_reg';
                    }
                    $nestedData[] = $etat[0]['facture'];
                }
                $nestedData[] = $value;
                $k++;
            }
            // $nestedData[] ='nbr';
            // $nestedData[] = 'date';
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

    #[Route('/annulation_preinscription', name: 'annulation_preinscription')]
    public function annulation_preinscription(Request $request)
    {   
        $ids = json_decode($request->get('idpreins'));
        foreach ($ids as $id) {
            $preinscription = $this->em->getRepository(TPreinscription::class)->find($id);
            $preinscription->setInscriptionValide(0);
            // $preinscription->setUserUpdated($this->getUser());
            $preinscription->setUpdated(New DateTime('now'));
        }
        $this->em->flush();
        return new Response(json_encode(1));
    }
    
    #[Route('/admission_preinscription', name: 'admission_preinscription')]
    public function admissionPreinscription(Request $request): Response
    {
        $ids = json_decode($request->get('idpreins'));
        foreach ($ids as $id) {
            $preinscription = $this->em->getRepository(TPreinscription::class)->find($id);
            $preinscription->setCategorieListe(
                $this->em->getRepository(PStatut::class)->find(1)
            );
            $preinscription->setAdmissionListe(
                $this->em->getRepository(PStatut::class)->find(5)
            );
            $this->em->flush();

            // $operationcab = new TOperationcab();
            // $operationcab->setPreinscription($preinscription);
            // $operationcab->setAnnee($preinscription->getAnnee());
            // $operationcab->setOrganisme($this->em->getRepository(POrganisme::class)->find(7));
            // $operationcab->setCategorie('inscription');
            // $operationcab->setCreated(new DateTime('now'));
            // $operationcab->setUserCreated($this->getUser());
            // $this->em->persist($operationcab);
            // $this->em->flush();
            // $etab = $preinscription->getAnnee()->getFormation()->getEtablissement()->getAbreviation();
            // $operationcab->setCode($etab.'-FAC'.str_pad($operationcab->getId(), 8, '0', STR_PAD_LEFT).'/'.date('Y'));
            // $this->em->flush();
            
        }
        return new JsonResponse('Admission bien enregister', 200);
    }

    #[Route('/frais_preins_modals/{id}', name: 'frais_preins_modals')]
    public function frais_preins_modals(Request $request,TPreinscription $preinscription): Response
    {   
        $etudiant = $preinscription->getEtudiant();
        $natutre = $preinscription->getNature();
        $annee = $preinscription->getAnnee();
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

    #[Route('/article_frais/{id}', name: 'article_frais')]
    public function article_frais(Request $request,TPreinscription $preinscription): Response
    {   
        $operationcab = $this->em->getRepository(TOperationcab::class)->findOneBy(['preinscription'=>$preinscription,'categorie'=>'pré-inscription']);
        $formation = $preinscription->getAnnee()->getFormation();
        $frais = $this->em->getRepository(PFrais::class)->findBy(['formation'=>$formation,'categorie'=>'Pré-inscription','active'=>1]);
        $data = "<option selected enabled>Choix Fraix</option>";
        foreach ($frais as $frs) {
            $data .="<option value=".$frs->getId()." data-id=".$frs->getmontant().">".$frs->getDesignation()."</option>";
        }
        return new JsonResponse(['list' => $data, 'codefacture' => $operationcab->getCode()], 200); 
        // return new JsonResponse($data, 200);
    }

    #[Route('/addfrais/{id}', name: 'addfrais')]
    public function addfrais(Request $request,TPreinscription $preinscription): Response
    {   
        $ids = json_decode($request->get('frais'));
        // dd($request);
        $operationcab = $this->em->getRepository(TOperationcab::class)->findOneBy(['preinscription'=>$preinscription,'categorie'=>'pré-inscription']);
        if ($operationcab->getActive() == 0) {
            return new JsonResponse('Facture Cloturée', 500);
        }
        foreach($ids as $idfrais){
            $operationdet = new TOperationdet();
            $operationdet->setOperationcab($operationcab);
            $operationdet->setFrais($this->em->getRepository(PFrais::class)->find($idfrais->id));
            $operationdet->setMontant($idfrais->montant);
            $operationdet->setRemise(0);
            $operationdet->setActive(1);
            $operationdet->setCreated(new DateTime('now'));
            $operationdet->setUpdated(new DateTime('now'));
            $operationdet->setOrganisme($this->em->getRepository(POrganisme::class)->find($idfrais->organisme_id));
            $this->em->persist($operationdet);
            $this->em->flush();
            $operationdet->setCode('OPD'.str_pad($operationdet->getId(), 8, '0', STR_PAD_LEFT));
            $this->em->flush();
        };
        return new JsonResponse($operationcab->getId(), 200);
    }

    #[Route('/getdoc_preinscription/{id}', name: 'getdoc_preinscription')]
    public function getdoc_preinscription(Request $request,TPreinscription $preinscription): Response
    {
        // $documentsExists = $this->em->getRepository(TPreinscription::class)->findBy(['preinscription' => $admission->getPreinscription()]);
        $documentsExists = $preinscription->getDocuments();
        $etablissement = $preinscription->getAnnee()->getFormation()->getEtablissement();
        if(count($documentsExists) > 0) {
            $documents = $this->em->getRepository(PDocument::class)->getDocumentDoesNotExistPreisncriptions($preinscription, $etablissement);
        } else {
            $documents = $this->em->getRepository(PDocument::class)->findBy([
                'etablissement'=>$etablissement,'attribution'=>'PREINSCRIPTION',
                'active'=>1,
            ]);
        }
        // dd($documentsExists);
        $documentHtml = "";
        $documentExistHtml = "";
        foreach ($documentsExists as $documentsExist) {
            $documentExistHtml .= '
            <li class="ms-elem-selection" id="'.$documentsExist->getId().'" >
                <span> '.$documentsExist->getDesignation().' </span>
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
    
    #[Route('/adddocuments_preins', name: 'adddocuments_preins')]
    public function adddocuments_preins(Request $request): Response
    {
        $preinscription = $this->em->getRepository(TPreinscription::class)->find($request->get('idPreinscription'));
        $preinscription->addDocument($this->em->getRepository(PDocument::class)->find($request->get('idDocument')));
        $this->em->flush();
        return new JsonResponse('Bien Enregistre', 200);
    }

    #[Route('/deletedocuments_preins', name: 'deletedocuments_preins')]
    public function deletedocuments_preins(Request $request): Response
    {
        $preinscription = $this->em->getRepository(TPreinscription::class)->find($request->get('idPreinscription'));
        $preinscription->removeDocument($this->em->getRepository(PDocument::class)->find($request->get('idDocument')));
        $this->em->flush();
        return new JsonResponse('Bien Supprimer', 200);
    }

    #[Route('/attestation_preinscription/{preinscription}', name: 'attestation_preinscription')]
    public function attestationpreinscription(Request $request, TPreinscription $preinscription): Response
    {
        // $preinscription
        $html = $this->render("attestaion/pdfs/preinscription.html.twig", [
            'preinscription' => $preinscription,
            'annee' => $preinscription->getAnnee(),
            'etablissement' => $preinscription->getAnnee()->getFormation()->getEtablissement(),
            'formation' => $preinscription->getAnnee()->getFormation(),
            'etudiant' => $preinscription->getEtudiant(),
        ])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '12',
            'margin_right' => '12',
            ]);
        // $mpdf->SetTitle('Attestation de pré-inscription '.$preinscription->getEtudiant()->getNom().' '.$preinscription->getEtudiant()->getPrenom());
        $mpdf->SetTitle('Attestation de Pré-Inscription');
        $mpdf->SetHTMLHeader(
            $this->render("attestaion/pdfs/header.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("attestaion/pdfs/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("attestaion.pdf", "I");
    }
    

    #[Route('/cfc_preinscription/{preinscription}', name: 'cfc_preinscription')]
    public function cfc_preinscription(Request $request, TPreinscription $preinscription): Response
    {
        
        $html = $this->render("preinscription/pdfs/preinscription.html.twig", [
            'preinscription' => $preinscription
        ])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 
            // 'format' => [200, 350],
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            // 'margin_bottom' => 5,
            ]);
        // $mpdf->SetTitle('Attestation de pré-inscription '.$preinscription->getEtudiant()->getNom().' '.$preinscription->getEtudiant()->getPrenom());
        $mpdf->SetTitle('CFC de Pré-Inscription');
        // $mpdf->SetHTMLHeader(
        //     $this->render("attestaion/pdfs/header.html.twig")->getContent()
        // );
        $mpdf->SetHTMLFooter(
            $this->render("preinscription/pdfs/footer_preins.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("CFC Préinscription.pdf", "I");
    }

    #[Route('/facture/{operationcab}', name: 'preinscription_facture')]
    public function preinscriptionFacture(Request $request, TOperationcab $operationcab): Response
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
        // // dd($reglement, $operationDetails);
        // $html = $this->render("facture/pdfs/facture.html.twig", [
        //     'reglementTotal' => $reglementTotal,
        //     'operationTotal' => $operationTotal,
        //     'operationcab' => $operationcab
        // ])->getContent();
        // $mpdf = new Mpdf();
        // $mpdf->SetTitle('Facture');
        // $mpdf->SetHTMLHeader(
        //     $this->render("facture/pdfs/header.html.twig")->getContent()
        // );
        // $mpdf->SetHTMLFooter(
        //     $this->render("facture/pdfs/footer.html.twig")->getContent()
        // );
        // $mpdf->WriteHTML($html);
        // $mpdf->Output("facture.pdf", "I");
    }

    #[Route('/getEtudiantInfospreins/{preinscription}', name: 'getEtudiantInfospreins')]
    public function getEtudiantInfospreins(Request $request, TPreinscription $preinscription) 
    {
        // dd($preinscription);
        $situations = $this->em->getRepository(PSituation::class)->findBy([],['designation' => 'ASC']);
        $academies = $this->em->getRepository(XAcademie::class)->findBy([],['designation' => 'ASC']);
        $filieres = $this->em->getRepository(XFiliere::class)->findBy([],['designation' => 'ASC']);
        $typebacs = $this->em->getRepository(XTypeBac::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $langues = $this->em->getRepository(XLangue::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $natureDemandes = $this->em->getRepository(NatureDemande::class)->findBy(['active'=>1],['designation' => 'ASC']);
        
        $candidats_infos = $this->render("preinscription/pages/candidats_infos.html.twig", [
            'etudiant' => $preinscription->getEtudiant(),
            'situations' => $situations,
        ])->getContent();
        // dd($candidats_infos);
        $parents_infos = $this->render("preinscription/pages/parents_infos.html.twig", [
            'etudiant' => $preinscription->getEtudiant(),
            'situations' => $situations,
        ])->getContent();
        
        $academique_infos = $this->render("preinscription/pages/academique_infos.html.twig", [
            'etudiant' => $preinscription->getEtudiant(),
            'academies' => $academies,
            'filieres' => $filieres,
            'typebacs' => $typebacs,
            'langues' => $langues,
        ])->getContent();

        $divers = $this->render("preinscription/pages/divers.html.twig", [
            'preinscription' => $preinscription,
            'etudiant' => $preinscription->getEtudiant(),
            'situations' => $situations,
            'natureDemandes' => $natureDemandes,
        ])->getContent();

        $info_etudiant = [ 'candidats_infos' => $candidats_infos,
            'parents_infos' => $parents_infos,
            'academique_infos' => $academique_infos,
            'divers' => $divers,
        ];
        return new JsonResponse($info_etudiant);
    }

    
    #[Route('/edit_infos_preins/{preinscription}', name: 'edit_infos_preins')]
    public function edit_infos_preins(Request $request, TPreinscription $preinscription) 
    {
        if(!$preinscription){
            return new JsonResponse("Etudiant Introuvable!!",500);
        }
        if(
            empty($request->get('nom')) || empty($request->get('prenom')) || 
            empty($request->get('date_naissance')) || empty($request->get('lieu_naissance')) ||
            empty($request->get('nat_demande'))  || empty($request->get('st_famille')) ||
            empty($request->get('cin')) ||  empty($request->get('ville')) || 
            empty($request->get('tel1')) || empty($request->get('tel2')) || 
            empty($request->get('tel3')) || empty($request->get('mail1')) || empty($request->get('id_filiere')) || 
            empty($request->get('id_type_bac')) || empty($request->get('annee_bac')) || 
            empty($request->get('moyenne_bac')) || empty($request->get('moyen_regional')) ||
            empty($request->get('moyen_national'))
        ){return new JsonResponse("Merci de remplir tout les champs obligatoire!!",500); }
        $preinscription->getEtudiant()->setNom(strtoupper($request->get('nom')));
        $preinscription->getEtudiant()->setPrenom(ucfirst(strtolower($request->get('prenom'))));
        // $preinscription->getEtudiant()->setTitre($request->get('titre'));
        if ($request->get('date_naissance') != "") {
            $preinscription->getEtudiant()->setDateNaissance(new \DateTime($request->get('date_naissance')));
        }
        $preinscription->getEtudiant()->setLieuNaissance($request->get('lieu_naissance'));
        $preinscription->getEtudiant()->setSexe($request->get('sexe'));
        if ($request->get('st_famille') != "") {
            $preinscription->getEtudiant()->setStFamille($this->em->getRepository(PSituation::class)->find($request->get('st_famille')));
        }
        $preinscription->getEtudiant()->setNationalite(strtoupper($request->get('nationalite')));
        $preinscription->getEtudiant()->setCin(strtoupper($request->get('cin')));
        $preinscription->getEtudiant()->setPasseport(strtoupper($request->get('passeport')));
        $preinscription->getEtudiant()->setVille(strtoupper($request->get('ville')));
        $preinscription->getEtudiant()->setTel1($request->get('tel1'));
        $preinscription->getEtudiant()->setTelPere($request->get('tel2'));
        $preinscription->getEtudiant()->setTelMere($request->get('tel3'));
        
        $preinscription->getEtudiant()->setMail1(strtoupper($request->get('mail1')));
        $preinscription->getEtudiant()->setMail2(strtoupper($request->get('mail2')));
        $preinscription->getEtudiant()->setAdresse(strtoupper($request->get('adresse')));

        if ($request->get('situation_parents') != "") {
            $preinscription->getEtudiant()->setStFamilleParent($this->em->getRepository(PSituation::class)->find($request->get('situation_parents')));
        }
        $preinscription->getEtudiant()->setNomPere(strtoupper($request->get('nom_p')));
        $preinscription->getEtudiant()->setPrenomPere(ucfirst(strtolower($request->get('prenom_p'))));
        $preinscription->getEtudiant()->setNationalitePere(strtoupper($request->get('nationalite_p')));
        $preinscription->getEtudiant()->setProfessionPere(strtoupper($request->get('profession_p')));
        $preinscription->getEtudiant()->setEmployePere(strtoupper($request->get('employe_p')));
        $preinscription->getEtudiant()->setCategoriePere(strtoupper($request->get('categorie_p')));
        $preinscription->getEtudiant()->setTelPere($request->get('tel_p'));
        $preinscription->getEtudiant()->setMailPere(strtoupper($request->get('mail_p')));
        $preinscription->getEtudiant()->setSalairePere($request->get('salaire_p'));

        $preinscription->getEtudiant()->setNomMere(strtoupper($request->get('nom_m')));
        $preinscription->getEtudiant()->setPrenomMere(ucfirst(strtolower($request->get('prenom_m'))));
        $preinscription->getEtudiant()->setNationaliteMere(strtoupper($request->get('nationalite_m')));
        $preinscription->getEtudiant()->setProfessionMere(strtoupper($request->get('profession_m')));
        $preinscription->getEtudiant()->setEmployeMere(strtoupper($request->get('employe_m')));
        $preinscription->getEtudiant()->setCategorieMere(strtoupper($request->get('categorie_m')));
        $preinscription->getEtudiant()->setTelMere($request->get('tel_m'));
        $preinscription->getEtudiant()->setMailMere(strtoupper($request->get('mail_m')));
        $preinscription->getEtudiant()->setSalaireMere(strtoupper($request->get('salaire_m')));

        $preinscription->getEtudiant()->setCne($request->get('cne'));
        if ($request->get('id_academie') != "") {
            $preinscription->getEtudiant()->setAcademie($this->em->getRepository(XAcademie::class)->find($request->get('id_academie')));
        }
        if ($request->get('id_filiere') != "") {
            $preinscription->getEtudiant()->setFiliere($this->em->getRepository(XFiliere::class)->find($request->get('id_filiere')));
        }
        if ($request->get('id_type_bac') != "") {
            $preinscription->getEtudiant()->setTypeBac($this->em->getRepository(XTypeBac::class)->find($request->get('id_type_bac')));
        }
        $preinscription->getEtudiant()->setAnneeBac($request->get('annee_bac'));
        $preinscription->getEtudiant()->setMoyenneBac($request->get('moyenne_bac'));
        $preinscription->getEtudiant()->setMoyenRegional($request->get('moyen_regional'));
        $preinscription->getEtudiant()->setMoyenNational($request->get('moyen_national'));
        $preinscription->getEtudiant()->setObs($request->get('obs'));
        $preinscription->getEtudiant()->setCategoriePreinscription(strtoupper($request->get('categorie_preinscription') == "" ? $preinscription->getEtudiant()->getCategoriePreinscription() : $request->get('categorie_preinscription')));
        if ($request->get('langue_concours') != "") {
            $preinscription->getEtudiant()->setLangueConcours($this->em->getRepository(XLangue::class)->find($request->get('langue_concours')));
        }
        if ($request->get('concours_medbup') != "") {
            $preinscription->getEtudiant()->setConcoursMedbup($request->get('concours_medbup'));
        }
        $preinscription->getEtudiant()->setBourse($request->get('bourse'));
        $preinscription->getEtudiant()->setLogement($request->get('logement'));
        $preinscription->getEtudiant()->setParking($request->get('parking'));
        if ($request->get('nat_demande') != "") {
            // $preinscription->getEtudiant()->setNatureDemande($this->em->getRepository(NatureDemande::class)->find($request->get('nat_demande')));
            $preinscription->setNature($this->em->getRepository(NatureDemande::class)->find($request->get('nat_demande')));
        }
        $preinscription->getEtudiant()->setEtablissement($request->get('etablissement'));
        
        $preinscription->getEtudiant()->setUserUpdated($this->getUser());
        $preinscription->getEtudiant()->setUpdated(new \DateTime('now'));

        $this->em->flush();
        return new JsonResponse("Bien Modifier",200);
    }
    
    #[Route('/extraction_preins', name: 'extraction_preins')]
    public function extraction_preins()
    {   
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ORD');
        $sheet->setCellValue('B1', 'CODE CANDIDAT');
        $sheet->setCellValue('C1', 'CODE PREINSCRIPTION');
        $sheet->setCellValue('D1', 'NOM');
        $sheet->setCellValue('E1', 'PRENOM');
        $sheet->setCellValue('F1', 'DATE NAISSANCE');
        $sheet->setCellValue('G1', 'CIN');
        $sheet->setCellValue('H1', 'TEL CANDIDAT');
        $sheet->setCellValue('I1', 'MAIL CANDIDAT');
        $sheet->setCellValue('J1', 'ETABLISSEMENT');
        $sheet->setCellValue('K1', 'FORMATION');
        $sheet->setCellValue('L1', 'CATEGORIE DEMANDE');
        $sheet->setCellValue('M1', 'NATURE DEMANDE');
        $sheet->setCellValue('N1', 'TYPE DE BAC');
        $sheet->setCellValue('O1', 'ANNEE BAC');
        $sheet->setCellValue('P1', 'MOYENNE GENERALE');
        // $spreadsheet->getActiveSheet()->getStyle('P1')->getFill()
        // ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        $sheet->setCellValue('Q1', 'MOYENNE NATIONALE');
        $sheet->setCellValue('R1', 'MOYENNE REGIONALE');
        $sheet->setCellValue('S1', 'N°FACTURE');
        $sheet->setCellValue('T1', 'MONTANT FACTURE');
        $sheet->setCellValue('U1', 'MONTANT REGLE');
        $sheet->setCellValue('V1', 'RESTE');
        $sheet->setCellValue('W1', 'TYPE REGLEMENT');
        $sheet->setCellValue('X1', 'REFERENCE REGLEMENT');
        $sheet->setCellValue('Y1', 'DATE FACTURE');
        $sheet->setCellValue('Z1', 'D-CREATION REGLEMENT');
        $sheet->setCellValue('AA1', 'DATE REGLEMENT');
        // $sheet->setCellValue('AB1', 'U-PREINS');
        // $sheet->setCellValue('AC1', 'U-REGLEMENT');
        $i=2;
        $j=1;
        $current_year = date('m') > 7 ? $current_year = date('Y').'/'.date('Y')+1 : $current_year = date('Y') - 1 .'/' .date('Y');
        // $current_year = "2022/2023";
        $preinscriptions = $this->em->getRepository(TPreinscription::class)->getPreinsByCurrentYear($current_year);
        foreach ($preinscriptions as $preinscription) {
            $etudiant = $preinscription->getEtudiant();
            $natutre = $etudiant->getNatureDemande();
            $annee = $preinscription->getAnnee();
            $formation = $annee->getFormation();
            $sheet->setCellValue('A'.$i, $j);
            $sheet->setCellValue('B'.$i, $preinscription->getEtudiant()->getCode());
            $sheet->setCellValue('C'.$i, $preinscription->getCode());
            $sheet->setCellValue('D'.$i, $preinscription->getEtudiant()->getNom());
            $sheet->setCellValue('E'.$i, $preinscription->getEtudiant()->getPrenom());
            $sheet->setCellValue('F'.$i, $preinscription->getEtudiant()->getDateNaissance());
            $sheet->setCellValue('G'.$i, $preinscription->getEtudiant()->getCin());
            $sheet->setCellValue('H'.$i, $preinscription->getEtudiant()->getTel1());
            $sheet->setCellValue('I'.$i, $preinscription->getEtudiant()->getMail1());
            $sheet->setCellValue('J'.$i, $formation->getEtablissement()->getDesignation());
            $sheet->setCellValue('K'.$i, $formation->getDesignation());
            $sheet->setCellValue('L'.$i, $preinscription->getEtudiant()->getCategoriePreinscription());
            if ($etudiant->getNatureDemande()) {
                $sheet->setCellValue('M'.$i, $preinscription->getNature()->getDesignation());
            }
            $sheet->setCellValue('N'.$i, $etudiant->getTypeBac() == Null ? "" : $etudiant->getTypeBac()->getDesignation());
            $sheet->setCellValue('O'.$i, $etudiant->getAnneeBac());
            $sheet->setCellValue('P'.$i, $etudiant->getMoyenneBac());
            // $spreadsheet->getActiveSheet()->getStyle('P'.$i)->getFill()
            // ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
            $sheet->setCellValue('Q'.$i, $etudiant->getMoyenNational());
            $sheet->setCellValue('R'.$i, $etudiant->getMoyenRegional());
            $facture = $this->em->getRepository(TOperationcab::class)->findOneBy(['categorie'=>'pré-inscription','preinscription'=>$preinscription]);
            if ($facture) {
                $sheet->setCellValue('S'.$i, $facture->getCode());
                $sommefacture = $this->em->getRepository(TOperationdet::class)->getSumMontantByCodeFacture($facture);
                $sommefacture = $sommefacture == Null ? 0 : $sommefacture['total'];
                $sheet->setCellValue('T'.$i, $sommefacture);
                $sommereglement = $this->em->getRepository(TReglement::class)->getSumMontantByCodeFacture($facture);
                $sommereglement = $sommereglement == Null ? 0 : $sommereglement['total'];
                $sheet->setCellValue('U'.$i, $sommereglement);
                $reste = $sommefacture - $sommereglement;
                $sheet->setCellValue('V'.$i, $reste);
                $reglement = $this->em->getRepository(TReglement::class)->findOneBy(['operation'=>$facture],['id'=>'DESC']);
                if ($reglement) {
                    $sheet->setCellValue('W'.$i, $reglement->getPaiement()->getDesignation());
                    $sheet->setCellValue('X'.$i, $reglement->getCode());
                }
                $sheet->setCellValue('Y'.$i, $facture->getCreated());
                if ($reglement) {
                    $sheet->setCellValue('Z'.$i, $reglement->getCreated());
                    $sheet->setCellValue('AA'.$i, $reglement->getDateReglement());
                }
            }
            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Extraction Preinscription.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    
    #[Route('/print_documents_preinscription/{preinscription}', name: 'print_documents_preinscription')]
    public function print_documents_preinscription(TPreinscription $preinscription)
    {
        // dd($preinscription->getDocuments()[0]);
        // dd($preinscription->getNature());
        $documents = $this->em->getRepository(PDocument::class)->findBy([
            'attribution' => 'PREINSCRIPTION',
            'etablissement' => $preinscription->getAnnee()->getFormation()->getEtablissement(),
            'active' => 1,
        ]);
        // dd($documents);
        $html = $this->render("preinscription/pdfs/documents_preins.html.twig", [
            'preinscription' => $preinscription,
            'documents' => $documents,
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
