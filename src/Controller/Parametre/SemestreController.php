<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Entity\AcSemestre;
use App\Entity\AcPromotion;
use App\Entity\AcEtablissement;
use App\Controller\DatatablesController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/parametre/semestre')]
class SemestreController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_semestre')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'parametre_semestre', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/semestre/index.html.twig', [
            'etablissements' => $this->em->getRepository(AcEtablissement::class)->findBy(['active' => 1]),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_semestre_list')]
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
        if (!empty($params->all('columns')[2]['search']['value'])) {
            // dd("in");
            $filtre .= " and prm.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }
        $columns = array(
            array( 'db' => 'sem.id','dt' => 0),
            array( 'db' => 'LOWER(etab.designation)','dt' => 1),
            array( 'db' => 'LOWER(form.designation)','dt' => 2),
            array( 'db' => 'LOWER(prm.designation)','dt' => 3),
            array( 'db' => 'sem.designation','dt' => 4), 
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from ac_semestre sem
        inner join ac_promotion prm on prm.id = sem.promotion_id
        inner join ac_formation form on form.id = prm.formation_id
        inner join ac_etablissement etab on etab.id = form.etablissement_id
        $filtre ";
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
    #[Route('/new', name: 'parametre_semestre_new')]
    public function new(Request $request): Response
    {
        // dd($request);
       $semestre = new AcSemestre();
       $semestre->setDesignation($request->get('designation'));
       $semestre->setActive($request->get('active') == "on" ? true : false);
       $semestre->setCreated(new \DateTime("now"));
       $semestre->setPromotion(
           $this->em->getRepository(AcPromotion::class)->find($request->get("promotion_id"))
       );
       $this->em->persist($semestre);
       $this->em->flush();
       $semestre->setCode("SEM".str_pad($semestre->getId(), 8, '0', STR_PAD_LEFT));
       $this->em->flush();

       return new JsonResponse(1);
    }
    #[Route('/details/{semestre}', name: 'parametre_semestre_details')]
    public function details(AcSemestre $semestre): Response
    {
       return new JsonResponse([
           'designation' => $semestre->getDesignation(),
           'active' => $semestre->getActive()
       ]);
    }
    #[Route('/update/{semestre}', name: 'parametre_semestre_update')]
    public function update(Request $request, AcSemestre $semestre): Response
    {
        $semestre->setDesignation($request->get('designation'));
        $semestre->setActive($request->get('active') == "on" ? true : false);
        $this->em->flush();
 
        return new JsonResponse(1);
    }
    
}
