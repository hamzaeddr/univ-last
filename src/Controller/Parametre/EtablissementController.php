<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\AcEtablissement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parametre/etablissement')]
class EtablissementController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_etablissement')]
    public function index(Request $request)
    {
        $operations = ApiController::check($this->getUser(), 'parametre_etablissement', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/etablissement/index.html.twig',[
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_etablissement_list')]
    public function gestionInscriptionList(Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'etab.id','dt' => 0),
            array( 'db' => 'etab.designation','dt' => 1),
            array( 'db' => 'etab.abreviation','dt' => 2),
            array( 'db' => 'etab.nature','dt' => 3),
            array( 'db' => 'DATE_FORMAT(etab.date, "%d/%m/%Y")','dt' => 4),
           
           
            
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM ac_etablissement etab
        
        $filtre "
        ;
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
    #[Route('/new', name: 'parametre_etablissement_new')]
    public function new(Request $request): Response
    {
        // dd($request);
       $etablissement = new AcEtablissement();
       $etablissement->setDesignation($request->get('designation'));
       $etablissement->setAbreviation($request->get('abreviation'));
       $etablissement->setNature($request->get('nature'));
       $etablissement->setActive($request->get('active') == "on" ? true : false);
       $etablissement->setDate(new \DateTime($request->get('date')));
       $this->em->persist($etablissement);
       $this->em->flush();
       $etablissement->setCode("ETA".str_pad($etablissement->getId(), 8, '0', STR_PAD_LEFT));
       $this->em->flush();
       
       return new JsonResponse(1);
    }
    #[Route('/details/{etablissement}', name: 'parametre_etablissement_details')]
    public function details(AcEtablissement $etablissement): Response
    {
       return new JsonResponse([
           'designation' => $etablissement->getDesignation(),
           'abreviation' => $etablissement->getAbreviation(),
           'nature' => $etablissement->getNature(),
           'date' => date_format($etablissement->getDate(), "Y-m-d"),
           'active' => $etablissement->getActive()
       ]);
    }
    #[Route('/update/{etablissement}', name: 'parametre_etablissement_update')]
    public function update(Request $request, AcEtablissement $etablissement): Response
    {
        $etablissement->setDesignation($request->get('designation'));
        $etablissement->setAbreviation($request->get('abreviation'));
        $etablissement->setNature($request->get('nature'));
        $etablissement->setActive($request->get('active') == "on" ? true : false);
        $etablissement->setDate(new \DateTime($request->get('date')));
        $this->em->flush();
 
        return new JsonResponse(1);
    }
}
