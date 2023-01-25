<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Entity\AcPromotion;
use App\Entity\AcEtablissement;
use App\Controller\DatatablesController;
use App\Entity\AcFormation;
use App\Entity\PEnseignant;
use App\Entity\PGrade;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/parametre/enseignant')]

class EnseignantController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_enseignant')]
    public function index(Request $request)
    {
        $operations = ApiController::check($this->getUser(), 'parametre_enseignant', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/enseignant/index.html.twig', [
            'grades' => $this->em->getRepository(PGrade::class)->findAll(),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_enseignant_list')]
    public function list(Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1";   
        // dd($params->all('columns')[0]);
        if (!empty($params->all('columns')[0]['search']['value'])) {
            // dd("in");
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            // dd("in");
            $filtre .= " and form.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }
        $columns = array(
            array( 'db' => 'ens.id','dt' => 0),
            array( 'db' => 'upper(ens.code)','dt' => 1),
            array( 'db' => 'upper(ens.nom)','dt' => 2),
            array( 'db' => 'upper(ens.prenom)','dt' => 3),
            array( 'db' => 'upper(grd.designation)','dt' => 4),
            array( 'db' => 'upper(grd.abreviation)','dt' => 5),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from penseignant ens
        inner join pgrade grd on grd.id = ens.grade_id $filtre ";
        // dd($sql);
        $totalRows .= $sql;
        $sqlRequest .= $sql;
        $stmt = $this->em->getConnection()->prepare($sql);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());
        // dd($sql);
            
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
            $nestedData = array();
            $cd = $row['id'];
            // dd($row);
            
            foreach (array_values($row) as $key => $value) {
               
                $nestedData[] = $value;
                
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
    #[Route('/new', name: 'parametre_enseignant_new')]
    public function new(Request $request): Response
    {
    //    dd($request);
       if (empty($request->get('nom')) || empty($request->get('prenom')) || $request->get('grade') == "") {
            return new JsonResponse('Merci de remplir tout les champs!!',500);
       }
       $enseignant = new PEnseignant();
       $enseignant->setNom($request->get('nom'));
       $enseignant->setPrenom($request->get('prenom'));
       $enseignant->setCreated(new \DateTime('now'));
       $enseignant->setGrade($this->em->getRepository(PGrade::class)->find($request->get('grade')));
       $this->em->persist($enseignant);
       $this->em->flush();
       $enseignant->setCode("ENS".str_pad($enseignant->getId(), 8, '0', STR_PAD_LEFT));
       $this->em->flush();

       return new JsonResponse('Enseignant Bien Ajouté',200);
    }
    #[Route('/details/{enseignant}', name: 'parametre_enseignant_details')]
    public function details(PEnseignant $enseignant): Response
    {
       
        $html = $this->render('parametre/enseignant/pages/modifier.html.twig', [
            'enseignant' => $enseignant,
            'grades' => $this->em->getRepository(PGrade::class)->findAll(),
       ])->getContent();
       return new JsonResponse($html,200);
    }
    #[Route('/update/{enseignant}', name: 'parametre_enseignant_update')]
    public function update(Request $request, PEnseignant $enseignant): Response
    {
        if (empty($request->get('nom')) || empty($request->get('prenom')) || $request->get('grade') == "") {
             return new JsonResponse('Merci de remplir tout les champs!!',500);
        }
        $enseignant->setNom($request->get('nom'));
        $enseignant->setPrenom($request->get('prenom'));
        $enseignant->setGrade($this->em->getRepository(PGrade::class)->find($request->get('grade')));
        $this->em->flush();
 
        return new JsonResponse('Enseignant bien modifier!',200);
    }
}
