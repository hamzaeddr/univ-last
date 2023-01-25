<?php

namespace App\Controller\AdministrationEpreuve;

use DateTime;
use Mpdf\Mpdf;
use App\Entity\PStatut;
use App\Entity\ExGnotes;
use App\Entity\AcEpreuve;
use App\Entity\PEnseignant;
use App\Entity\TInscription;
use App\Entity\AcEtablissement;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\ExMnotes;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as reader;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/administration/note')]
class NoteEpreuveController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        
    }
    #[Route('/', name: 'administration_note')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'administration_note', $this->em,$request);

        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $professeurs = $this->em->getRepository(PEnseignant::class)->findAll();
        return $this->render('administration_epreuve/note_epreuve.html.twig', [
            'etablissements' => $etbalissements,
            'professeurs' => $professeurs,
            'operations' => $operations
        ]);
    }

    #[Route('/list', name: 'list_note_epreuve')]
    public function list_gestion_preinscription(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " and mdl.active = 1 and ann.cloture_academique = 'non' and ann.validation_academique = 'non' and epv.statut_id in (29,30) ";
        
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and forma.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $filtre .= " and prm.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[3]['search']['value'])) {
            $filtre .= " and sem.id = '" . $params->all('columns')[3]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[4]['search']['value'])) {
            $filtre .= " and mdl.id = '" . $params->all('columns')[4]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[5]['search']['value'])) {
            $filtre .= " and ele.id = '" . $params->all('columns')[5]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[6]['search']['value'])) {
            $filtre .= " and ens.id = '" . $params->all('columns')[6]['search']['value'] . "' ";
        } 

        $columns = array(
            array( 'db' => 'epv.id','dt' => 0 ),
            array( 'db' => 'right (epv.code , 10)','dt' => 1),
            array( 'db' => 'DATE_FORMAT(epv.date_epreuve,"%Y-%m-%d")','dt' => 2),
            array( 'db' => 'left(mdl.designation , 8)','dt' => 3),
            array( 'db' => 'left(ele.designation , 8)','dt' => 4),
            array( 'db' => 'left(etab.abreviation , 10)','dt' => 5),
            array( 'db' => 'left(forma.abreviation , 10)','dt' => 6),
            array( 'db' => 'lower(prm.designation)','dt' => 7),
            array( 'db' => 'left(CONCAT(ens.nom,"  ",ens.prenom) , 10)','dt' => 8),
            array( 'db' => 'nepv.abreviation','dt' => 9),
            array( 'db' => 'nbr_effectif','dt' => 10),
            array( 'db' => 'nbr_absence','dt' => 11),
            array( 'db' => 'nbr_saisi','dt' => 12),
            array( 'db' => 'nbr_non_saisi','dt' => 13),
            array( 'db' => 'stat.designation','dt' => 14),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM ac_epreuve epv 
        INNER JOIN ac_element ele ON ele.id = epv.element_id
        INNER JOIN ac_module mdl ON mdl.id = ele.module_id
        INNER JOIN ac_semestre sem ON sem.id = mdl.semestre_id
        INNER JOIN ac_promotion prm ON prm.id = sem.promotion_id
        INNER JOIN ac_formation forma ON forma.id = prm.formation_id
        INNER JOIN ac_etablissement etab ON etab.id = forma.etablissement_id
        left JOIN ac_epreuve_penseignant epvens ON epvens.ac_epreuve_id = epv.id
        left JOIN penseignant ens ON ens.id = epvens.penseignant_id
        INNER JOIN pstatut stat ON stat.id = epv.statut_id
        INNER JOIN pnature_epreuve nepv ON nepv.id = epv.nature_epreuve_id
        INNER JOIN ac_annee ann on ann.id = epv.annee_id
        INNER JOIN (SELECT epreuve_id,COUNT(id) nbr_effectif FROM ex_gnotes GROUP BY epreuve_id) ne ON ne.epreuve_id = epv.id 
        LEFT JOIN (SELECT epreuve_id,COUNT(id) nbr_absence FROM ex_gnotes WHERE absence = '1' GROUP BY epreuve_id) na ON na.epreuve_id = epv.id
        LEFT JOIN (SELECT epreuve_id, COUNT(id) nbr_saisi FROM ex_gnotes WHERE (absence = '0' or absence is null)  AND (note IS NOT NULL AND note <> '') GROUP BY epreuve_id) ni ON ni.epreuve_id = epv.id 
        LEFT JOIN (SELECT epreuve_id, COUNT(id) nbr_non_saisi FROM ex_gnotes WHERE (absence = '0' or absence is null) AND (note IS NULL OR note = '' ) GROUP BY epreuve_id) nni ON nni.epreuve_id = epv.id Where 1=1 $filtre";
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
            // dump($row);die;
            $nestedData = array();
            $cd = $row['id'];
            $nestedData[] = "<input type ='checkbox' class='check_admissible' id ='$cd' >";
            // $nestedData[] = $i;
            $etat_bg="";
            foreach (array_values($row) as $key => $value) { 
                $nestedData[] = $value;
            }
            ///lst add*

            if ($row['nbr_saisi'] == 0) {
                $etat_bg = 'etat_bg_nf';
            } elseif ($row['nbr_saisi'] > 0 AND $row['nbr_saisi'] < ($row['nbr_effectif'] - $row['nbr_absence'])) {
                $etat_bg = '';
            } else {
                $etat_bg = 'etat_bg_reg';
                
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

    #[Route('/list/note_inscription/{id_epruve}', name: 'list_note_inscription')]
    public function note_inscription(Request $request, $id_epruve): Response
    {   
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where prv.id = $id_epruve ";
        $columns = array(
            array( 'db' => 'ex.id','dt' => 0 ),
            array( 'db' => 'upper(ins.id)','dt' => 1 ),
            array( 'db' => 'etu.nom','dt' => 2),
            array( 'db' => 'etu.prenom','dt' => 3),
            array( 'db' => 'ex.note','dt' => 4),
            array( 'db' => 'ex.absence','dt' => 5),
            array( 'db' => 'ex.observation','dt' => 6),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from ex_gnotes ex
        INNER JOIN ac_epreuve prv on prv.id = ex.epreuve_id
        INNER JOIN tinscription ins on ins.id = ex.inscription_id
        INNER JOIN tadmission adm on adm.id = ins.admission_id
        INNER JOIN tpreinscription prei on prei.id = adm.preinscription_id
        INNER JOIN tetudiant etu on etu.id = prei.etudiant_id $filtre";
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
        // dd($sqlRequest);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        $data = array();
        $i = 1;
        foreach ($result as $key => $row) {
            // dump($row['id']);
            $nestedData = array();
            $cd = $row['id'];
            // $nestedData[] = $cd;
            $etat_bg="";
            foreach (array_values($row) as $key => $value) { 
                if($key > 0) {
                    if($key == 4){
                        $value = "<form class='save_note' id ='$i'><input type ='float' name='input_note' class='input_note' id='$cd' value='$value' min='0.0' max='20.0' placeholder='-'></form>";
                    }
                    if($key == 5){
                        if($value == 0) {
                            $value = "<center><input type ='checkbox' class='check_note_ins' id ='$cd'></center>";
                        }else{
                            $value = "<center><input type ='checkbox' class='check_note_ins' id ='$cd' checked></center>";
                        }
                    }
                    if ($key == 6) {
                        $value = "<form class='save_obs' id ='$i'> <input type='text' class='obs_note' id='$cd' name='input_obs' value='$value'> </form>";
                    }
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
    
    #[Route('/note_update/{id}', name: 'note_update')]
    public function note_update(Request $request, ExGnotes $exgnotes): Response
    {   
        if(empty($request->get('input_note')) || $request->get('input_note')){
            $exgnotes->setNote($request->get('input_note') == "" ?  NULL : $request->get('input_note'));
        }
        if(empty($request->get('input_obs')) || $request->get('input_obs')){
            // dd('fff');
            $exgnotes->setObservation($request->get('input_obs') == "" ? NULL : $request->get('input_obs'));
        }
        // dd('test');
        if($request->get('absence')){
            if($request->get('absence') == 'true'){
                $exgnotes->setAbsence(1);
            }else{
                $exgnotes->setAbsence(0);
            }
        }
        $this->em->flush();
        return new Response('Note Bien Changé');
    }
    #[Route('/observation_update/{id}', name: 'observation_update')]
    public function observation_update(Request $request, ExGnotes $exgnotes): Response
    {   
        if(empty($request->get('input_obs')) || $request->get('input_obs')){
            $exgnotes->setObservation($request->get('input_obs') == "" ? NULL : $request->get('input_obs'));
        }
        // dd('test');
        if($request->get('absence')){
            if($request->get('absence') == 'true'){
                $exgnotes->setAbsence(1);
            }else{
                $exgnotes->setAbsence(0);
            }
        }
        $this->em->flush();
        return new Response('Note Bien Changé');
    }
    #[Route('/absence_update/{id}', name: 'absence_update')]
    public function absence_update(Request $request, ExGnotes $exgnotes): Response
    {   
        if($request->get('absence')){
            if($request->get('absence') == 'true'){
                $exgnotes->setAbsence(1);
            }else{
                $exgnotes->setAbsence(0);
            }
        }
        $this->em->flush();
        return new Response('Note Bien Changé');
    }
    #[Route('/canvas/{id}', name: 'administration_note_epreuve_canvas')]
    public function noteepreuveCanvas(AcEpreuve $epreuve) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'nom');
        $sheet->setCellValue('C1', 'prenom');
        $sheet->setCellValue('D1', 'note');
        $sheet->setCellValue('E1', 'absence');
        $sheet->setCellValue('F1', 'observation');
        if($epreuve->getAnonymat() == 1){
            $sheet->setCellValue('G1', 'Anonymat');
        }
        $i=2;
        $gnotes = $this->em->getRepository(ExGnotes::class)->ExgnotesOrderByNom($epreuve);
        foreach($gnotes as $gnote) {
            $sheet->setCellValue('A'.$i, $gnote->getInscription()->getId());
            $sheet->setCellValue('B'.$i, $gnote->getInscription()->getAdmission()->getPreinscription()->getEtudiant()->getNom());
            $sheet->setCellValue('C'.$i, $gnote->getInscription()->getAdmission()->getPreinscription()->getEtudiant()->getPrenom());
            $sheet->setCellValue('D'.$i, $gnote->getNote());
            $sheet->setCellValue('E'.$i, $gnote->getAbsence() ? '1' : '');
            $sheet->setCellValue('F'.$i, $gnote->getObservation());
            if($epreuve->getAnonymat() == 1){
                $sheet->setCellValue('G'.$i, $gnote->getAnonymat());
            }
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'epreuves_'.$epreuve->getId().'.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/import/{id}', name: 'administration_note_import')]
    public function epreuveEnMasse(Request $request, SluggerInterface $slugger,AcEpreuve $epreuve) {
        $file = $request->files->get('file');
        // dd($file);
        if(!$file){
            return new JsonResponse('Prière d\'importer le fichier',500);
        }
        if($file->guessExtension() !== 'xlsx'){
            return new JsonResponse('Prière d\'enregister un fichier xlsx', 500);            
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'_'.$this->getUser()->getUsername().'.'.$file->guessExtension();

        // Move the file to the directory where brochures are stored
        try {
            $file->move(
                $this->getParameter('note_epreuve_create_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception($e);
        }
        $reader = new reader();
        $spreadsheet = $reader->load($this->getParameter('note_epreuve_create_directory').'/'.$newFilename);
        $worksheet = $spreadsheet->getActiveSheet();
        $spreadSheetArys = $worksheet->toArray();

        unset($spreadSheetArys[0]);
        $sheetCount = count($spreadSheetArys);

        foreach ($spreadSheetArys as $sheet) {
            // dd();
            $inscription = $this->em->getRepository(TInscription::class)->find($sheet[0]);
            $exgnote = $this->em->getRepository(ExGnotes::class)->findOneBy(['epreuve'=>$epreuve,'inscription'=>$inscription]);
            
            if($sheet[3] == "" ) {
                $exgnote->setNote(NULL);
            } else if($sheet[3] > 20) {
                $exgnote->setNote(20);
            } else if ($sheet[3] < 0) {
                $exgnote->setNote(0);
            } else {
                $exgnote->setNote($sheet[3]);
            }
            $exgnote->setAbsence($sheet[4]);
            $exgnote->setObservation($sheet[5]);
            $this->em->flush();
        }
        return new JsonResponse("Total des notes associé est ".$sheetCount);
    }

    
   
}
