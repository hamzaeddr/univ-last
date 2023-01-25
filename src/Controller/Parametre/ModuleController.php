<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\AcEtablissement;
use App\Entity\AcModule;
use App\Entity\AcSemestre;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parametre/module')]
class ModuleController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_module')]
    public function index(Request $request)
    {
        
        $operations = ApiController::check($this->getUser(), 'parametre_module', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/module/index.html.twig', [
            'etablissements' => $this->em->getRepository(AcEtablissement::class)->findBy(['active' => 1]),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_module_list')]
    public function list(Request $request)
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
        if (!empty($params->all('columns')[3]['search']['value'])) {
            // dd("in");
            $filtre .= " and sem.id = '" . $params->all('columns')[3]['search']['value'] . "' ";
        }
        $columns = array(
            array( 'db' => 'mdl.id','dt' => 0),
            array( 'db' => 'LOWER(etab.designation)','dt' => 1),
            array( 'db' => 'LOWER(form.designation)','dt' => 2),
            array( 'db' => 'LOWER(prm.designation)','dt' => 3),
            array( 'db' => 'LOWER(sem.designation)','dt' => 4),
            array( 'db' => 'mdl.designation','dt' => 5), 
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from ac_module mdl
        inner join ac_semestre sem on sem.id = mdl.semestre_id
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
    #[Route('/new', name: 'parametre_module_new')]
    public function new(Request $request)
    {
        // dd($request);
       $module = new AcModule();
       $module->setDesignation($request->get('designation'));
       $module->setActive($request->get('active') == "on" ? true : false);
       $module->setCreated(new \DateTime("now"));
       $module->setType($request->get('type'));
       $module->setColor($request->get('color'));
       $module->setSemestre(
           $this->em->getRepository(AcSemestre::class)->find($request->get("semestre_id"))
       );
       $module->setUserCreated($this->getUser());
       $module->setCoefficient($request->get("coefficient"));
       $this->em->persist($module);
       $this->em->flush();
       $module->setCode("MOD".str_pad($module->getId(), 8, '0', STR_PAD_LEFT));
       $this->em->flush();

       return new JsonResponse(1);
    }
    #[Route('/details/{module}', name: 'parametre_module_details')]
    public function details(AcModule $module): Response
    {
        $html = $this->render('parametre/module/pages/modifier.html.twig', [
             'module' => $module
        ])->getContent();
        return new JsonResponse($html,200);
    }
    #[Route('/update/{module}', name: 'parametre_module_update')]
    public function update(Request $request, AcModule $module): Response
    {
        $module->setDesignation($request->get('designation'));
        $module->setCoefficient($request->get("coefficient"));
        $module->setActive($request->get('active') == "on" ? true : false);
        $module->setUpdated(new \DateTime("now"));
        $module->setType($request->get('type'));
        $module->setColor($request->get('color'));
        $module->setUserUpdated($this->getUser());
        $this->em->flush();
 
        return new JsonResponse(1);
    }
}
