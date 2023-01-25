<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Entity\AcEtablissement;
use App\Controller\DatatablesController;
use App\Entity\AcFormation;
use App\Entity\PEnseignant;
use App\Entity\PEnseignantExcept;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/parametre/enseignantexcept')]

class EnseignantExceptController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_enseignantexcept')]
    public function index(Request $request)
    {
        $operations = ApiController::check($this->getUser(), 'parametre_enseignantexcept', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/enseignantexcept/index.html.twig', [
            'etablissements' => $this->em->getRepository(AcEtablissement::class)->findBy(['active' => 1]),
            'enseignants' => $this->em->getRepository(PEnseignant::class)->findAll(),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_enseignantexcept_list')]
    public function list(Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where 1 = 1 ";   
        // dd($params->all('columns')[1]['search']['value']);
        if (!empty($params->all('columns')[0]['search']['value'])) {
            // dd("in");
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            // dd("in");
            $filtre .= " and frm.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[2]['search']['value'])) {
            // dd("in");
            $filtre .= " and ens.id = '" . $params->all('columns')[2]['search']['value'] . "' ";
        }
        $columns = array(
            array( 'db' => 'excp.id','dt' => 0),
            array( 'db' => 'UPPER(ens.nom)','dt' => 1),
            array( 'db' => 'UPPER(ens.prenom)','dt' => 2),
            array( 'db' => 'upper(etab.designation)','dt' => 3),
            array( 'db' => 'upper(frm.designation)','dt' => 4), 
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM  penseignant_except excp
        inner join penseignant ens on ens.id = excp.enseignant_id
        inner join ac_formation frm on frm.id = excp.formation_id
        inner join ac_etablissement etab on etab.id = frm.etablissement_id
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

    #[Route('/new', name: 'parametre_enseignantexcept_new')]
    public function new(Request $request): Response
    {
        $exist = $this->em->getRepository(PEnseignantExcept::class)->findBy(['formation'=>$request->get('formation_id'),'enseignant'=>$request->get('enseignant_id')]);
        // dd($exist);
        
        if ($exist != null) {
            return new JsonResponse('Cet enseignant est déja exist!',500);
        }
        // dd($request);
        $ensexcept = new PEnseignantExcept();
        $ensexcept->setFormation($this->em->getRepository(AcFormation::class)->find($request->get('formation_id')));
        $ensexcept->setEnseignant($this->em->getRepository(PEnseignant::class)->find($request->get('enseignant_id')));
        $ensexcept->setCreated(new \DateTime());
        $ensexcept->setUsercreated($this->getUser());
        $this->em->persist($ensexcept);
        $this->em->flush();

       return new JsonResponse('Enseignant Bien Ajouter',200);
    }
    #[Route('/delete', name: 'parametre_enseignantexcept_delete')]
    public function delete(Request $request): Response
    {
        $enseignantexcept = $this->em->getRepository(PEnseignantExcept::class)->find($request->get('enseignantexcept'));
        // dd($exist);
        
        if ($enseignantexcept == null) {
            return new JsonResponse('Merci de choisir un enseignant!',500);
        }
        // dd($request);
        $this->em->remove($enseignantexcept);
        $this->em->flush();

       return new JsonResponse('Enseignant Bien Supprimer',200);
    }
}
