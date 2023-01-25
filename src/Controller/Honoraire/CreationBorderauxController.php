<?php

namespace App\Controller\Honoraire;

use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\AcEtablissement;
use App\Entity\AcPromotion;
use App\Entity\HAlbhon;
use App\Entity\HHonens;
use App\Entity\PEnseignant;
use App\Entity\PGrade;
use App\Entity\Semaine;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/honoraire/creation_borderaux')]
class CreationBorderauxController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'creation_borderaux')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'creation_borderaux', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etbalissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        $professeurs = $this->em->getRepository(PEnseignant::class)->findAll();
        $grades = $this->em->getRepository(PGrade::class)->findAll();
        $semaines = $this->em->getRepository(Semaine::class)->findAll();
        return $this->render('honoraire/creation_borderaux.html.twig', [
            'etablissements' => $etbalissements,
            'operations' => $operations,
            'semaines' => $semaines,
            'grades' => $grades,
            'professeurs' => $professeurs,
        ]);
    }
    

    #[Route('/list', name: 'list_creation_borderaux')]
    public function list_creation_borderaux(Request $request): Response
    {   
         
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where emp.annuler = 0 and hon.statut='R' and ann.validation_academique = 'non' ";
        
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
            $filtre .= " and gr.id = '" . $params->all('columns')[4]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[5]['search']['value'])) {
            $filtre .= " and sm.id = '" . $params->all('columns')[5]['search']['value'] . "' ";
        }   
        if (!empty($params->all('columns')[6]['search']['value'])) {
            $filtre .= " and ens.id = '" . $params->all('columns')[6]['search']['value'] . "' ";
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
            array( 'db' => 'lower(nat.abreviation)','dt' => 15),
            array( 'db' => 'hon.statut','dt' => 16),
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
                    $borderau = $this->em->getRepository(HHonens::class)->find($cd)->getBordereau();
                    if ($borderau != NULL) {
                        $checked = "checked='' disabled='' class='check_seance'";
                    }
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
        // dd($nestedData);
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        return new Response(json_encode($json_data));
    }
    
    #[Route('/cree_borderaux', name: 'cree_borderaux')]
    public function cree_borderaux(Request $request): Response
    {
        $ids = json_decode($request->get('ids_seances'));
        if($ids == NULL || empty($request->get('promotion')) || empty($request->get('semaine')) ){
            return new JsonResponse('Merci de Choisir une semestre et une semaine et au moins une ligne!',500);
        }
        $halbhon = new HAlbhon();
        
        $halbhon->setPromotion($this->em->getRepository(AcPromotion::class)->find($request->get('promotion')));
        $halbhon->setSemaine($this->em->getRepository(Semaine::class)->find($request->get('semaine')));
        $halbhon->setUserCreated($this->getUser());
        $halbhon->setCreated(new \DateTime('now'));
        $this->em->persist($halbhon);
        $this->em->flush();
        $halbhon->setCode('BRD'.str_pad($halbhon->getId(), 8, '0', STR_PAD_LEFT));
        $this->em->flush();
        
        foreach ($ids as $id) {
            $honens = $this->em->getRepository(HHonens::class)->find($id);
            $honens->setBordereau($this->em->getRepository(HAlbhon::class)->find($halbhon->getId()));
            $this->em->flush();
        }
        return new JsonResponse($halbhon->getId(),200);
    }
    
    #[Route('/honoraire_borderaux/{borderaux}', name: 'honoraire_borderaux')]
    public function honoraireborderaux(HAlbhon $borderaux)
    {  
        $honenss = $borderaux->getHonenss();
        // dd($borderaux->getHonenss()[0]->getSeance()->getProgrammation()->getElement()->getNature()->getDesignation());
        $html = $this->render("honoraire/pdfs/borderaux.html.twig", [
            'borderaux' => $borderaux,
            'honenss' => $honenss
        ])->getContent();
        $mpdf = new Mpdf([
            'format' => 'A4-L',
            'mode' => 'utf-8',
            'margin_top' => '5',
            'margin_left' => '5',
            'margin_right' => '5',
            ]);
        $mpdf->SetTitle('ETAT DES HONORAIRES PAR PROFESSEUR');
        $mpdf->SetHTMLFooter(
            $this->render("facture/pdfs/footer_borderaux.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        $mpdf->Output("Borderaux.pdf", "I");
    }
    
    
    #[Route('/findSemaine', name: 'findSemaine')]
    public function findSemaine(Request $request): Response
    {
        // dd($request->query->get("search"));
        $semaine = $this->em->getRepository(Semaine::class)->findSemaine($request->query->get("search"));
        // $html = '<option value="">Choix semaine</option>';
        $list['id'] = $semaine->getId();
        $list['nsemaine'] = $semaine->getNsemaine();
        $list['debut'] = $semaine->getDateDebut()->format('j/m');
        $list['fin'] = $semaine->getDateFin()->format('j/m');
        // dd($semaine);
        // dd($list);
        return new JsonResponse($list);
    }
    
}
