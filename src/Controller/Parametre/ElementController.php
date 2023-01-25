<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\AcElement;
use App\Entity\AcEtablissement;
use App\Entity\AcModule;
use App\Entity\TypeElement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parametre/element')]
class ElementController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_element')]
    public function index(Request $request)
    {
        $operations = ApiController::check($this->getUser(), 'parametre_element', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('parametre/element/index.html.twig', [
            'etablissements' => $this->em->getRepository(AcEtablissement::class)->findBy(['active' => 1]),
            'natures' => $this->em->getRepository(TypeElement::class)->findAll(),
            'operations' => $operations
        ]);
    }
    #[Route('/list', name: 'parametre_element_list')]
    public function list(Request $request)
    {
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1";   
        // dd($params->all('columns')[0]);
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $filtre .= " and etab.id = '" . $params->all('columns')[0]['search']['value'] . "' ";
        }
        if (!empty($params->all('columns')[1]['search']['value'])) {
            $filtre .= " and form.id = '" . $params->all('columns')[1]['search']['value'] . "' ";
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
        $columns = array(
            array( 'db' => 'elm.id','dt' => 0),
            array( 'db' => 'LOWER(etab.designation)','dt' => 1),
            array( 'db' => 'LOWER(form.designation)','dt' => 2),
            array( 'db' => 'LOWER(prm.designation)','dt' => 3),
            array( 'db' => 'LOWER(sem.designation)','dt' => 4),
            array( 'db' => 'LOWER(mdl.designation)','dt' => 5), 
            array( 'db' => 'elm.designation','dt' => 6), 
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        from ac_element elm
        inner join ac_module mdl on mdl.id = elm.module_id
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
    #[Route('/new', name: 'parametre_element_new')]
    public function new(Request $request)
    {       
       $element = new AcElement();
       $element->setDesignation($request->get('designation'));
       $element->setActive($request->get('active') == "on" ? true : null);
       $element->setCreated(new \DateTime("now"));
       $element->setModule(
           $this->em->getRepository(AcModule::class)->find($request->get("module_id"))
       );
       $element->setNature(
           $this->em->getRepository(TypeElement::class)->find($request->get("nature"))
       );
       $element->setUserCreated($this->getUser());
       $element->setCoefficient($request->get("coefficient"));
       $coefficient_epreuve['NAT000000001'] = $request->get('coefficient_cc');
       $coefficient_epreuve['NAT000000002'] = $request->get('coefficient_tp');
       $coefficient_epreuve['NAT000000003'] = $request->get('coefficient_ef');
       $element->setCoefficientEpreuve($coefficient_epreuve);
       $element->setCoursDocument($request->get('cours_document') == "on" ? true : false);
       $this->em->persist($element);
       $this->em->flush();
       $element->setCode("ELE".str_pad($element->getId(), 8, '0', STR_PAD_LEFT));
       $this->em->flush();

       return new JsonResponse('Element bien ajouter',200);
    }
    #[Route('/details/{element}', name: 'parametre_element_details')]
    public function details(AcElement $element): Response
    {
       $html = $this->render('parametre/element/pages/modifier.html.twig', [
            'element' => $element,
            'natures' => $this->em->getRepository(TypeElement::class)->findAll(),
       ])->getContent();
       return new JsonResponse($html,200);
    }

    #[Route('/update/{element}', name: 'parametre_element_update')]
    public function update(Request $request, AcElement $element): Response
    {   
        if (empty($request->get('designation')) || empty($request->get('coefficient'))) {
            return new JsonResponse('merci de remplir tout les champs!!',500);
        }
        $element->setDesignation($request->get('designation'));
        $element->setActive($request->get('active') == "on" ? true : false);
        $element->setCoursDocument($request->get('cours_document') == "on" ? true : null);
        $element->setUpdated(new \DateTime("now"));
        $element->setNature(
            $this->em->getRepository(TypeElement::class)->find($request->get("nature"))
        );
        $element->setUserUpdated($this->getUser());
        $element->setCoefficient($request->get("coefficient"));
        $coefficient_epreuve['NAT000000001'] = $request->get('coefficient_cc');
        $coefficient_epreuve['NAT000000002'] = $request->get('coefficient_tp');
        $coefficient_epreuve['NAT000000003'] = $request->get('coefficient_ef');
        $element->setCoefficientEpreuve($coefficient_epreuve);
        $this->em->flush();
    
        return new JsonResponse('Element Bien Modifier',200);
    }
}
