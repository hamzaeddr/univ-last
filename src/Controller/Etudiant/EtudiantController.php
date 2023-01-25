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
use App\Entity\AcFormation;
use App\Entity\PMatiere;
use App\Entity\POrganisme;
use App\Entity\PSituation;
use App\Entity\XFiliere;
use App\Entity\XLangue;
use App\Entity\TOperationcab;
use App\Entity\TPreinscriptionReleveNote;
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

#[Route('/etudiant/etudiants')]
class EtudiantController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'etudiant_index')]
    public function index(Request $request): Response
    {
        //check if user has access to this page
        $operations = ApiController::check($this->getUser(), 'etudiant_index', $this->em, $request);
        // dd($operations);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $situations = $this->em->getRepository(PSituation::class)->findBy([],['designation' => 'ASC']);
        $academies = $this->em->getRepository(XAcademie::class)->findBy([],['designation' => 'ASC']);
        $filieres = $this->em->getRepository(XFiliere::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $typebacs = $this->em->getRepository(XTypeBac::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $langues = $this->em->getRepository(XLangue::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $natureDemandes = $this->em->getRepository(NatureDemande::class)->findBy(['active' => 1],['designation' => 'ASC']);
        
        return $this->render('etudiant/etudiant/index.html.twig', [
            'operations' => $operations,
            'situations' => $situations,
            'filieres' => $filieres,
            'typebacs' => $typebacs,
            'langues' => $langues,
            'natureDemandes' => $natureDemandes,
            'academies' => $academies,
        ]);
    }
    
    #[Route('/list', name: 'etudiant_list')]
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
            array( 'db' => 'etu.code','dt' => 1),
            array( 'db' => 'etu.nom','dt' => 2),
            array( 'db' => 'etu.prenom','dt' => 3),
            array( 'db' => 'nd.designation','dt' => 4),
            array( 'db' => 'LOWER(xtb.designation)','dt' => 5),
            array( 'db' => 'etu.moyenne_bac','dt' => 6),
            array( 'db' => 'etu.tel1','dt' => 7),
            array( 'db' => 'etu.tel2','dt' => 8),
            array( 'db' => 'LOWER(st.designation)','dt' => 9),
            array( 'db' => 'etu.tele_liste','dt' => 10),
            array( 'db' => 'etu.created','dt' => 11 )
        );
        // dd($columns);
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                FROM tetudiant etu
                left join pstatut st on st.id = etu.statut_id
                left join nature_demande nd on nd.id = etu.nature_demande_id
                left join xtype_bac xtb on xtb.id = etu.type_bac_id 
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
        $sqlRequest .= DatatablesController::Order($request, $columns);
        // dd($sqlRequest);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        $data = array();
        foreach ($result as $key => $row) {
            // dump($row);
            $nestedData = array();
            $cd = $row['id'];
            // $nestedData[] = $cd;
            $i = 0;
            foreach (array_values($row) as $key => $value) {
                if($i == 9) {
                    $nestedData[] = count($this->em->getRepository(TEtudiant::class)->find($row['id'])->getPreinscriptions()) > 0 ? 'Valider' : 'N.V';
                } 
                $nestedData[] = $value;
                $i++;
            }
            $nestedData[] = 'N.R';
            $nestedData["DT_RowId"] = $cd;
            $nestedData["DT_RowClass"] = "";
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

  
    #[Route('/import', name: 'etudiant_import')]
    public function etudiantImport(Request $request, SluggerInterface $slugger) {
        $file = $request->files->get('file');
        if(!$file){
            return new JsonResponse('Prière d\'importer le fichier', 500);
        }
        if($file->guessExtension() !== 'xlsx'){
            return new JsonResponse('Prière d\'enregister un fichier xlsx', 500);            
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'_'.$this->getUser()->getUserIdentifier().'.'.$file->guessExtension();

        // Move the file to the directory where brochures are stored
        try {
            $file->move(
                $this->getParameter('etudiant_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            return new JsonResponse($e, 500);
        }
        $reader = new Reader();
        $spreadsheet = $reader->load($this->getParameter('etudiant_directory').'/'.$newFilename);
        $worksheet = $spreadsheet->getActiveSheet();
        $spreadSheetArys = $worksheet->toArray();
        unset($spreadSheetArys[0]);
        $sheetCount = count($spreadSheetArys);
        $exist = 0;
        $inserted = 0;
        $exist_array = [];
        // dd($spreadSheetArys);
        foreach ($spreadSheetArys as $sheet) {
            if ($sheet[8] != "" || empty($sheet[8])) {
                $etudiantExist = $this->em->getRepository(TEtudiant::class)->findOneBy(['cin' => $sheet[8],'nom'=>$sheet[2]]);
            
                // dd($etudiantExist);
                if($etudiantExist != null) {
                    $exist++;
                    array_push($exist_array, [
                        $etudiantExist->getId()
                    ]);
                } else {
                    $etudiant = new TEtudiant();
                    $etudiant->setNom(strtoupper($sheet[2]));
                    $etudiant->setPrenom(ucfirst(strtolower($sheet[3])));
                    $date = new DateTime();
                    $etudiant->setDateNaissance($date->setTimestamp(strtotime($sheet[4])));
                    $etudiant->setLieuNaissance($sheet[5]);
                    $etudiant->setSexe($sheet[6]);
                    $etudiant->setTeleListe('Intéressé');
                    // $etudiant->setStFamille();
                    // $etudiant->setStFamilleParent();
                    $etudiant->setNationalite($sheet[7]);
                    $etudiant->setCin($sheet[8]);
                    $etudiant->setVille($sheet[9]);
                    $etudiant->setTel1($sheet[10]);
                    $etudiant->setMail1($sheet[11]);
                    // $etudiant->setPasseport($sheet[11]);
                    // $etudiant->setAdresse($sheet[12]);
                    $etudiant->setNomPere($sheet[12]);
                    $etudiant->setPrenomPere($sheet[13]);
                    $etudiant->setTelPere($sheet[14]);
                    $etudiant->setMailPere($sheet[15]);
                    // $etudiant->setNationalitePere($sheet[21]);
                    // $etudiant->setProfessionPere($sheet[22]);
                    // $etudiant->setEmployePere($sheet[23]);
                    // $etudiant->setCategoriePere($sheet[24]);
                    // $etudiant->setSalairePere($sheet[27]);
                    $etudiant->setNomMere($sheet[16]);
                    $etudiant->setPrenomMere($sheet[17]);
                    // $etudiant->setNationaliteMere($sheet[30]);
                    // $etudiant->setProfessionMere($sheet[31]);
                    // $etudiant->setEmployeMere($sheet[32]);
                    // $etudiant->setCategorieMere($sheet[33]);
                    $etudiant->setTelMere($sheet[18]);
                    $etudiant->setMailMere($sheet[19]);
                    // $etudiant->setSalaireMere($sheet[36]);
                    $etudiant->setCne($sheet[20]);
                    $etudiant->setAcademie(
                        $this->em->getRepository(XAcademie::class)->findOneBy(['code' => $sheet[21]])
                    );
                    $etudiant->setEtablissement($sheet[22]);
                    $etudiant->setFiliere(
                        $this->em->getRepository(XFiliere::class)->findOneBy(["designation" => $sheet[23]])
                    );
                    $etudiant->setTypeBac(
                        $this->em->getRepository(XTypeBac::class)->findOneBy(['code' => $sheet[24]])
                    );
                    $etudiant->setAnneeBac($sheet[25]);
                    $etudiant->setMoyenneBac(str_replace(',', '.', $sheet[26]));
                    // $etudiant->setMoyenneRegional(str_replace(',', '.', $sheet[27]));
                    // $etudiant->setLangueConcours($sheet[44]);
                    // $etudiant->setNombreEnfants($sheet[45]);
                    $etudiant->setNatureDemande(
                        $this->em->getRepository(NatureDemande::class)->findOneBy(['code' => $sheet[27]])
                    );
                    // if ($sheet[47] == "oui") {
                    //     $etudiant->setBourse(1);
                    // }
                    // if ($sheet[48] == "oui"){
                    //     $etudiant->setLogement(1);
                    // }
                    // if ($sheet[49] == "oui") {
                    //     $etudiant->setParking(1);
                    // }                    
                    // if ($sheet[50] == "oui") {
                    //     $etudiant->setCpgem(1);
                    // }
                    if ($sheet[29] == "oui") {
                        $etudiant->setCpge2(1);
                    }                    
                    if ($sheet[30] == "oui") {
                        $etudiant->setCpge1(1);
                    }
                    // if ($sheet[53] == "oui") {
                    //     $etudiant->setVet(1);
                    // }
                    if ($sheet[31] == "oui") {
                        $etudiant->setCam(1);
                    }
                    if ($sheet[32] == "oui") {
                        $etudiant->setIst(1);
                    }
                    if ($sheet[33] == "oui") {
                        $etudiant->setIp(1);
                    }                    
                    if ($sheet[34] == "oui") {
                        $etudiant->setFpa(1);
                    }
                    if ($sheet[35] == "oui") {
                        $etudiant->setFma(1);
                    }
                    if ($sheet[36] == "oui") {
                        $etudiant->setFda(1);
                    }                   
                
                    $etudiant->setSourceSite(1);
                    $etudiant->setUserCreated($this->getUser());
                    $etudiant->setCreated(new DateTime('now'));
                    $etudiant->setStatut(
                        $this->em->getRepository(PStatut::class)->find(20)
                    );
                    $this->em->persist($etudiant);
                    $this->em->flush();
                    $etudiant->setCode('CND_UA'.str_pad($etudiant->getId(), 8, '0', STR_PAD_LEFT).'/'.date('Y'));
                    $this->em->flush();

                    $inserted++;

                }
            } 
        }

        $session = $request->getSession();
        $session->set('arrayOfExistEtudiant', $exist_array);
        return new JsonResponse([
            'inserted' => $inserted,
            'existed' => $exist
        ]);
    }
    #[Route('/download', name: 'etudiant_exist')]
    public function download(Request $request): Response
    {

        $session = $request->getSession();
        $arraysOfEtudiant = $session->get('arrayOfExistEtudiant');
        $spreadsheetExist = new Spreadsheet();
        $sheetExist = $spreadsheetExist->getActiveSheet();
        $sheetExist->setCellValue('A1', 'code');
        $sheetExist->setCellValue('B1', 'nom');
        $sheetExist->setCellValue('C1', 'prenom');
        $sheetExist->setCellValue('D1', 'cin');
        $i = 2;
        // dd($arraysOfEtudiant);
        foreach ($arraysOfEtudiant as $etudiant) {
            $etudiantExist = $this->em->getRepository(TEtudiant::class)->find($etudiant[0]);
            $sheetExist->setCellValue('A'.$i , $etudiantExist->getCode());
            $sheetExist->setCellValue('B'.$i , $etudiantExist->getNom());
            $sheetExist->setCellValue('C'.$i , $etudiantExist->getPrenom());
            $sheetExist->setCellValue('D'.$i , $etudiantExist->getCin());
            $i++;
        }
        
        $writer = new Xlsx($spreadsheetExist);
        $fileName = 'etudiant_exists.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // $session->remove('arrayOfExistEtudiant');
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);

       
    }
    #[Route('/etudiant_valider/{etudiant}', name: 'etudiant_valider')]
    public function etudiant_valider(Request $request,TEtudiant $etudiant): Response
    {   
        $currentMonth = date('m');
        $id_formation = $request->get('formation');
        $nature = $this->em->getRepository(NatureDemande::class)->find($request->get('naturedemande'));
        $formation = $this->em->getRepository(AcFormation::class)->find($id_formation);
        if(strpos($formation->getDesignation(), 'Résidanat') === false){
            $currentYear = date('Y') - 1 .'/'.date('Y');  // means current college year
            if($currentMonth >= 4) { // april and above
                $currentYear = date('Y') . '/'.date('Y')+1; // means next college year
            }
            $annee = $this->em->getRepository(AcAnnee::class)->findOneBy(['formation'=>$formation,'designation'=>$currentYear]);
        }else{
            $id_annee = $request->get('annee');
            $annee = $this->em->getRepository(AcAnnee::class)->find($id_annee);
        }
        if(!$annee) {
            return new JsonResponse('Annee untrouvable!', 500);
        }
        $exist = count($this->em->getRepository(TPreinscription::class)->findBy(['etudiant'=>$etudiant,'annee'=>$annee]));
        // dd($exist);
        if ($exist > 0) {
            return new JsonResponse("Etudiant déja une preinscription dans cette année / formation", 500);
        }
        // dd($etudiant->getStatut());
        $preinscription = new TPreinscription();
        $preinscription->setStatut($etudiant->getStatut());
        $preinscription->setEtudiant($etudiant);
        $preinscription->setInscriptionValide(1);
        $preinscription->setRangP(0);
        $preinscription->setRangS(0);
        $preinscription->setActive(1);
        $preinscription->setNature($nature);
        $preinscription->setCreated(new DateTime('now'));
        $preinscription->setAnnee($annee);
        $this->em->persist($preinscription);
        $this->em->flush();
        $preinscription->setCode('PRE-'.$formation->getAbreviation().str_pad($preinscription->getId(), 8, '0', STR_PAD_LEFT).'/'.date('Y'));
        $this->em->flush();

        $operationcab = new TOperationcab();
        $operationcab->setPreinscription($preinscription);
        $operationcab->setAnnee($preinscription->getAnnee());
        $operationcab->setOrganisme($this->em->getRepository(POrganisme::class)->find(7));
        $operationcab->setCategorie('pré-inscription');
        $operationcab->setCreated(new DateTime('now'));
        $operationcab->setUserCreated($this->getUser());
        $operationcab->setActive(1);
        $this->em->persist($operationcab);
        $this->em->flush();
        $etab = $preinscription->getAnnee()->getFormation()->getEtablissement()->getAbreviation();
        $operationcab->setCode($etab.'-FAC'.str_pad($operationcab->getId(), 8, '0', STR_PAD_LEFT).'/'.date('Y'));
        $this->em->flush();
        
        return new JsonResponse('Bien Enregistrer');
    }
    
    #[Route('/list/preinscription/{etudiant}', name: 'list_etudiant_preinscription')]
    public function listPreinscription(Request $request,TEtudiant $etudiant): Response
    {   
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 and pre.etudiant_id =" . $etudiant->getId();        
        $columns = array(
            array( 'db' => 'pre.code','dt' => 0 ),
            array( 'db' => 'etu.nom','dt' => 1),
            array( 'db' => 'etu.prenom','dt' => 2),
            array( 'db' => 'etab.abreviation','dt' => 3),
            array( 'db' => 'UPPER(form.abreviation)','dt' => 4),
        );
        // dd($columns);
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                      
                FROM tpreinscription pre
                inner join tetudiant etu on etu.id = pre.etudiant_id
                inner join ac_annee an on an.id = pre.annee_id
                inner join ac_formation form on form.id = an.formation_id              
                inner join ac_etablissement etab on etab.id = form.etablissement_id           

                $filtre"
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
        $sqlRequest .= DatatablesController::Order($request, $columns);
        // dd($sqlRequest);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();


        $data = array();
        // dd($result);
        $i = 1;
        foreach ($result as $key => $row) {
            // dump($row);
            $nestedData = array();
            $cd = $i;
            $nestedData[] = $cd;
            foreach (array_values($row) as $key => $value) {
                $nestedData[] = $value;
            }
            $data[] = $nestedData;
            // dd($nestedData);
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

    #[Route('/matiere/{etudiant}', name: 'etudiant_exist_matiere')]
    public function getMatiere(Request $request, TEtudiant $etudiant) 
    {
        $matiereExist = '';
        $i = 1;
        $arrayOfExistMatiere = [];
        foreach ($etudiant->getTPreinscritionReleveNotes() as $etudiantMatiere) {
            $matiereExist .= "
                <tr>
                    <td>".$i."</td> 
                    <td>".$etudiantMatiere->getMatiere()->getDesignation()."</td> 
                    <td>".$etudiantMatiere->getNote()."</td>
                    <td><i class='delete_matiere fa fa-trash'  id='".$etudiantMatiere->getId()."' style='cursor:pointer; color:red' ></i></td>
                </tr>";
            $i++;
            array_push($arrayOfExistMatiere, $etudiantMatiere->getMatiere()->getId());
        }
        // dd($matiereExist);
        $matiereNotSelected = $this->em->getRepository(PMatiere::class)->findAll();
        $data = "<option selected enabled>Selection une matiere</option>";
        foreach ($matiereNotSelected as $matiere) {
           if(!in_array($matiere->getId(), $arrayOfExistMatiere)) {
                $data .="<option value=".$matiere->getId().">".$matiere->getDesignation()."</option>";
           }
        }
        return new JsonResponse([
            'table' => $matiereExist,
            'matieres' => $data
        ]);
    }
    #[Route('/addmatiere/{etudiant}', name: 'etudiant_add_matiere')]
    public function addMatiere(Request $request, TEtudiant $etudiant) 
    {
        // $matiere = $request->get('matiere');
        $note = $request->get('note');
        // dd($matiere);
        $preinscriptioReleveNote = new TPreinscriptionReleveNote();
        $preinscriptioReleveNote->setUserCreated($this->getUser());
        $preinscriptioReleveNote->setNote($note);
        $preinscriptioReleveNote->setEtudiant($etudiant);
        $matiere = $this->em->getRepository(PMatiere::class)->find($request->get('matiere'));
        if ($matiere == NULL) {
            return new JsonResponse("Merci de choisir une matiere!",500);
        }
        $preinscriptioReleveNote->setMatiere($matiere);
        $preinscriptioReleveNote->setCreated(new \DateTime('now'));
        $this->em->persist($preinscriptioReleveNote);
        $this->em->flush();
       
        return new JsonResponse("Bien enregistre");
    }

    #[Route('/matiere/delete/{preinscriptionnote}', name: 'etudiant_remove_matiere')]
    public function removeMatiere(Request $request, TPreinscriptionReleveNote $preinscriptionnote) 
    {
        
        $this->em->remove($preinscriptionnote);
        $this->em->flush();
       
        return new JsonResponse("Bien enregistre");
    }

    
    #[Route('/getAppelRdv/{etudiant}', name: 'getAppelRdv')]
    public function getAppelRdv(Request $request, TEtudiant $etudiant) 
    {
        $rdv1 = $etudiant->getRdv1() == Null ? "" : $etudiant->getRdv1()->format('Y-m-j');
        $rdv2 = $etudiant->getRdv2() == Null ? "" : $etudiant->getRdv2()->format('Y-m-j');
        $appelrdv = [ 'rdv1' => $rdv1,
            'rdv2' => $rdv2,
        ];
        return new JsonResponse($appelrdv);
    }

    #[Route('/datedernierappel/{etudiant}', name: 'etudiant_dernier_appele')]
    public function dateDernierAppele(Request $request, TEtudiant $etudiant) 
    {
        
        $etudiant->setTeleListe($request->get('dateappelle'));
        $etudiant->setRdv1(new \DateTime($request->get('rdv1')));
        $etudiant->setRdv2(new \DateTime($request->get('rdv2')));
        $this->em->flush();
        return new JsonResponse("Bien enregistre");
    }

    #[Route('/statut/{etudiant}', name: 'etudiant_statut')]
    public function statutEtudiant(Request $request, TEtudiant $etudiant) 
    {
        $status = $this->em->getRepository(PStatut::class)->findBy(['table0' => 'etudiant']);
        $html = "<option value=''>Choix statut</option>";
        foreach ($status as $statut) {
            if($statut->getId() == $etudiant->getStatut()->getId()) {
                $html .= "<option value='".$statut->getId()."' selected>".$statut->getDesignation()."</option>";
            } else {
                $html .= "<option value='".$statut->getId()."'>".$statut->getDesignation()."</option>";
            }
        }
        return new JsonResponse($html);
    }
    #[Route('/statut/persist/{etudiant}', name: 'etudiant_statut_persist')]
    public function persistStatutEtudiant(Request $request, TEtudiant $etudiant) 
    {
        $statut = $this->em->getRepository(PStatut::class)->find($request->get('statut'));
        $etudiant->setStatut($statut);
        $this->em->flush();
        return new JsonResponse("Bien Enregistre");
    }
    #[Route('/getEtudiantInfos/{etudiant}', name: 'getEtudiantInfos')]
    public function getEtudiantInfos(Request $request, TEtudiant $etudiant) 
    {
        $situations = $this->em->getRepository(PSituation::class)->findBy([],['designation' => 'ASC']);
        $academies = $this->em->getRepository(XAcademie::class)->findBy([],['designation' => 'ASC']);
        $filieres = $this->em->getRepository(XFiliere::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $typebacs = $this->em->getRepository(XTypeBac::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $langues = $this->em->getRepository(XLangue::class)->findBy(['active'=>1],['designation' => 'ASC']);
        $natureDemandes = $this->em->getRepository(NatureDemande::class)->findBy(['active'=>1],['designation' => 'ASC']);
        
        $candidats_infos = $this->render("etudiant/etudiant/pages/candidats_infos.html.twig", [
            'etudiant' => $etudiant,
            'situations' => $situations,
        ])->getContent();
        
        $parents_infos = $this->render("etudiant/etudiant/pages/parents_infos.html.twig", [
            'etudiant' => $etudiant,
            'situations' => $situations,
        ])->getContent();
        
        $academique_infos = $this->render("etudiant/etudiant/pages/academique_infos.html.twig", [
            'etudiant' => $etudiant,
            'academies' => $academies,
            'filieres' => $filieres,
            'typebacs' => $typebacs,
            'langues' => $langues,
        ])->getContent();

        $divers = $this->render("etudiant/etudiant/pages/divers.html.twig", [
            'etudiant' => $etudiant,
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

    
    #[Route('/add_infos', name: 'add_infos')]
    public function add_infos(Request $request) 
    {
        if(
            empty($request->get('nom')) || empty($request->get('prenom')) || 
            empty($request->get('date_naissance')) || empty($request->get('lieu_naissance')) ||
            empty($request->get('nat_demande'))  || empty($request->get('st_famille')) || 
            empty($request->get('nationalite')) || empty($request->get('cin')) ||  empty($request->get('ville')) || 
            empty($request->get('tel1')) || empty($request->get('tel2')) || 
            empty($request->get('tel3')) || empty($request->get('mail1')) || 
            empty($request->get('id_filiere')) || empty($request->get('id_type_bac')) || 
            empty($request->get('annee_bac')) || empty($request->get('moyenne_bac')) || 
            empty($request->get('moyen_regional')) || empty($request->get('moyen_national')) || 
            empty($request->get('categorie_preinscription'))
        ){return new JsonResponse("Merci de remplir tout les champs obligatoire!!",500);}

        $etudiant = new TEtudiant();
        $etudiant->setNom(strtoupper($request->get('nom')));
        $etudiant->setPrenom(ucfirst(strtolower($request->get('prenom'))));
        $etudiant->setDateNaissance(new \DateTime($request->get('date_naissance')));
        $etudiant->setLieuNaissance(strtoupper($request->get('lieu_naissance')));
        $etudiant->setSexe(strtoupper($request->get('sexe')));
        $stfamille = $request->get('st_famille') == "" ? Null : $this->em->getRepository(PSituation::class)->find($request->get('st_famille'));
        $etudiant->setStFamille($stfamille);
        $etudiant->setNationalite(strtoupper($request->get('nationalite')));
        $etudiant->setCin(strtoupper($request->get('cin')));
        $etudiant->setPasseport(strtoupper($request->get('passeport')));
        $etudiant->setVille(strtoupper($request->get('ville')));
        $etudiant->setTel1($request->get('tel1'));
        $etudiant->setTelPere($request->get('tel2'));
        $etudiant->setTelMere($request->get('tel3'));
        $etudiant->setMail1(strtoupper($request->get('mail1')));
        $etudiant->setMail2(strtoupper($request->get('mail2')));
        $etudiant->setAdresse(strtoupper($request->get('adresse')));

        $situation = $request->get('situation_parents') == "" ? Null : $this->em->getRepository(PSituation::class)->find($request->get('situation_parents'));
        $etudiant->setStFamilleParent($situation);
        $etudiant->setNomPere(strtoupper($request->get('nom_p')));
        $etudiant->setPrenomPere(ucfirst(strtolower($request->get('prenom_p'))));
        $etudiant->setNationalitePere($request->get('nationalite_p'));
        $etudiant->setProfessionPere(strtoupper($request->get('profession_p')));
        $etudiant->setEmployePere(strtoupper($request->get('employe_p')));
        $etudiant->setCategoriePere(strtoupper($request->get('categorie_p')));
        // $etudiant->setTelPere($request->get('tel_p'));
        $etudiant->setMailPere($request->get('mail_p'));
        $etudiant->setSalairePere($request->get('salaire_p'));

        $etudiant->setNomMere(strtoupper($request->get('nom_m')));
        $etudiant->setPrenomMere(ucfirst(strtolower($request->get('prenom_m'))));
        $etudiant->setNationaliteMere($request->get('nationalite_m'));
        $etudiant->setProfessionMere($request->get('profession_m'));
        $etudiant->setEmployeMere($request->get('employe_m'));
        $etudiant->setCategorieMere($request->get('categorie_m'));
        // $etudiant->setTelMere($request->get('tel_m'));
        $etudiant->setMailMere($request->get('mail_m'));
        $etudiant->setSalaireMere($request->get('salaire_m'));

        $etudiant->setCne($request->get('cne'));
        $academie = $request->get('id_academie') == "" ? Null : $this->em->getRepository(XAcademie::class)->find($request->get('id_academie'));
        $etudiant->setAcademie($academie);
        $filiere = $request->get('id_filiere') == "" ? Null : $this->em->getRepository(XFiliere::class)->find($request->get('id_filiere'));
        $etudiant->setFiliere($filiere);
        $etudiant->setEtablissement(strtoupper($request->get('etablissement')));
        $typebacs = $request->get('id_type_bac') == "" ? Null : $this->em->getRepository(XTypeBac::class)->find($request->get('id_type_bac'));
        $etudiant->setTypeBac($this->em->getRepository(XTypeBac::class)->find($request->get('id_type_bac')));
        $etudiant->setAnneeBac($request->get('annee_bac'));
        $etudiant->setMoyenneBac($request->get('moyenne_bac'));
        $etudiant->setMoyenRegional($request->get('moyen_regional'));
        $etudiant->setMoyenNational($request->get('moyen_national'));
        $etudiant->setObs(strtoupper($request->get('obs')));
        if ($request->get('langue_concours') != "") {
            $etudiant->setLangueConcours($this->em->getRepository(XLangue::class)->find($request->get('langue_concours')));
        }
        $etudiant->setConcoursMedbup($request->get('concours_medbup'));

        $etudiant->setBourse($request->get('bourse'));
        $etudiant->setLogement($request->get('logement'));
        $etudiant->setParking($request->get('parking'));
        $etudiant->setNatureDemande($this->em->getRepository(NatureDemande::class)->find($request->get('nat_demande')));
        $etudiant->setCategoriePreinscription(strtoupper($request->get('categorie_preinscription')));
        
        $etudiant->setUserCreated($this->getUser());
        $etudiant->setCreated(new \DateTime('now'));
        $etudiant->setSourceSite(1);
        $etudiant->setStatut(
            $this->em->getRepository(PStatut::class)->find(20)
        );
        $this->em->persist($etudiant);
        $this->em->flush();

        $etudiant->setCode('CND_UA'.str_pad($etudiant->getId(), 8, '0', STR_PAD_LEFT).'/'.date('Y'));
        $this->em->flush();
        return new JsonResponse("Bien Enregistre",200);
    } 

    #[Route('/edit_infos/{etudiant}', name: 'edit_infos')]
    public function edit_infos(Request $request, TEtudiant $etudiant) 
    {
        if(!$etudiant){
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
        
        $etudiant->setNom(strtoupper($request->get('nom')));
        $etudiant->setPrenom(ucfirst(strtolower($request->get('prenom'))));
        $etudiant->setDateNaissance(new \DateTime($request->get('date_naissance')));
        $etudiant->setLieuNaissance($request->get('lieu_naissance'));
        $etudiant->setSexe($request->get('sexe'));
        $stfamille = $request->get('st_famille') == "" ? Null : $this->em->getRepository(PSituation::class)->find($request->get('st_famille'));
        $etudiant->setStFamille($stfamille);
        $etudiant->setNationalite(strtoupper($request->get('nationalite') == "" ? $etudiant->getNationalite() : $request->get('nationalite')));
        $etudiant->setCin(strtoupper($request->get('cin')));
        $etudiant->setPasseport(strtoupper($request->get('passeport')));
        $etudiant->setVille(strtoupper($request->get('ville')));
        $etudiant->setTel1($request->get('tel1'));
        $etudiant->setTelPere($request->get('tel2'));
        $etudiant->setTelMere($request->get('tel3'));
        $etudiant->setMail1(strtoupper($request->get('mail1')));
        $etudiant->setMail2($request->get('mail2'));
        $etudiant->setAdresse(strtoupper($request->get('adresse')));

        $situation = $request->get('situation_parents') == "" ? Null : $this->em->getRepository(PSituation::class)->find($request->get('situation_parents'));
        $etudiant->setStFamilleParent($situation);
        $etudiant->setNomPere(strtoupper($request->get('nom_p')));
        $etudiant->setPrenomPere(ucfirst(strtolower($request->get('prenom_p'))));
        $etudiant->setNationalitePere(strtoupper($request->get('nationalite_p') == "" ? $etudiant->getNationalitePere() : $request->get('nationalite_p')));
        $etudiant->setProfessionPere(strtoupper($request->get('profession_p')));
        $etudiant->setEmployePere(strtoupper($request->get('employe_p')));
        $etudiant->setCategoriePere(strtoupper($request->get('categorie_p')));
        // $etudiant->setTelPere($request->get('tel_p'));
        $etudiant->setMailPere(strtoupper($request->get('mail_p')));
        $etudiant->setSalairePere($request->get('salaire_p'));

        $etudiant->setNomMere(strtoupper($request->get('nom_m')));
        $etudiant->setPrenomMere(ucfirst(strtolower($request->get('prenom_m'))));
        $etudiant->setNationaliteMere(strtoupper($request->get('nationalite_m') == "" ? $etudiant->getNationaliteMere() : $request->get('nationalite_m')));
        $etudiant->setProfessionMere(strtoupper($request->get('profession_m')));
        $etudiant->setEmployeMere(strtoupper($request->get('employe_m')));
        $etudiant->setCategorieMere(strtoupper($request->get('categorie_m')));
        // $etudiant->setTelMere($request->get('tel_m'));
        $etudiant->setMailMere(strtoupper($request->get('mail_m')));
        $etudiant->setSalaireMere($request->get('salaire_m'));

        $etudiant->setCne($request->get('cne'));
        $id_academie = $request->get('id_academie') == "" ? Null : $this->em->getRepository(XAcademie::class)->find($request->get('id_academie'));
        $etudiant->setAcademie($id_academie);
        $id_filiere = $request->get('id_filiere') == "" ? Null : $this->em->getRepository(XFiliere::class)->find($request->get('id_filiere'));
        $etudiant->setFiliere($id_filiere);
        $id_type_bac = $request->get('id_type_bac') == "" ? Null : $this->em->getRepository(XTypeBac::class)->find($request->get('id_type_bac'));
        $etudiant->setTypeBac($id_type_bac);
        $etudiant->setEtablissement(strtoupper($request->get('etablissement')));
        $etudiant->setAnneeBac($request->get('annee_bac'));
        $etudiant->setMoyenneBac($request->get('moyenne_bac'));
        $etudiant->setMoyenRegional($request->get('moyen_regional'));
        $etudiant->setMoyenNational($request->get('moyen_national'));
        $etudiant->setObs(strtoupper($request->get('obs')));
        // $etudiant->setCategoriePreinscription(strtoupper($request->get('categorie_preinscription')));
        // $etudiant->setFraisPreinscription(strtoupper($request->get('frais_preinscription')));
        $xlangue = $request->get('langue_concours') == "" ? Null : $this->em->getRepository(XLangue::class)->find($request->get('langue_concours'));
        $etudiant->setLangueConcours($xlangue);
        $etudiant->setConcoursMedbup(strtoupper($request->get('concours_medbup')));

        $etudiant->setBourse($request->get('bourse'));
        $etudiant->setLogement($request->get('logement'));
        $etudiant->setParking($request->get('parking'));
        $nat_demande = $request->get('nat_demande') == "" ? Null : $this->em->getRepository(NatureDemande::class)->find($request->get('nat_demande'));
        $etudiant->setNatureDemande($nat_demande);
        $etudiant->setCategoriePreinscription(strtoupper($request->get('categorie_preinscription') == "" ? $etudiant->getCategoriePreinscription() : $request->get('categorie_preinscription')));
        
        $etudiant->setUserUpdated($this->getUser());
        $etudiant->setUpdated(new \DateTime('now'));

        $this->em->flush();
        return new JsonResponse("Bien Modifier",200);
    } 
}
