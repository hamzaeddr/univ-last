<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\AcEtablissement;
use App\Entity\AcFormation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parametre/formation')]
class FormationController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
            
    }
    #[Route('/', name: 'parametre_formation')]
    public function index(Request $request)
    {
        $operations = ApiController::check($this->getUser(), 'parametre_formation', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/formation/index.html.twig', [
            'etablissements' => $this->em->getRepository(AcEtablissement::class)->findBy(['active' => 1]),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_formation_list')]
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
        $columns = array(
            array( 'db' => 'form.id','dt' => 0),
            array( 'db' => 'LOWER(etab.designation)','dt' => 1),
            array( 'db' => 'form.designation','dt' => 2),
            array( 'db' => 'form.abreviation','dt' => 3),
            array( 'db' => 'form.nbr_annee','dt' => 4),
           
           
            
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from ac_formation form
        inner join ac_etablissement etab on etab.id = form.etablissement_id
        
        
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
    #[Route('/new', name: 'parametre_formation_new')]
    public function new(Request $request): Response
    {
        // dd($request);
       $formation = new AcFormation();
       $formation->setDesignation($request->get('designation'));
       $formation->setAbreviation($request->get('abreviation'));
       $formation->setNbrAnnee($request->get('duree'));
       $formation->setActive($request->get('active') == "on" ? true : false);
       $formation->setCreated(new \DateTime("now"));
       $formation->setEtablissement(
           $this->em->getRepository(AcEtablissement::class)->find($request->get("etablissement_id"))
       );
       $this->em->persist($formation);
       $this->em->flush();
       $formation->setCode("FOR".str_pad($formation->getId(), 8, '0', STR_PAD_LEFT));
       $this->em->flush();

       return new JsonResponse(1);
    }
    #[Route('/details/{formation}', name: 'parametre_formation_details')]
    public function details(AcFormation $formation): Response
    {
       return new JsonResponse([
           'designation' => $formation->getDesignation(),
           'abreviation' => $formation->getAbreviation(),
           'nbrAnnee' =>$formation->getNbrAnnee(),
           'active' => $formation->getActive()
       ]);
    }
    #[Route('/update/{formation}', name: 'parametre_formation_update')]
    public function update(Request $request, AcFormation $formation): Response
    {
        $formation->setDesignation($request->get('designation'));
        $formation->setAbreviation($request->get('abreviation'));
        $formation->setActive($request->get('active') == "on" ? true : false);
        $formation->setNbrAnnee($request->get('duree'));
        $this->em->flush();
 
        return new JsonResponse(1);
    }
}
