<?php

namespace App\Controller\Preinscription;

use DateTime;
use App\Entity\PFrais;
use App\Entity\PStatut;
use App\Entity\TOperation;
use App\Entity\TPreinscription;
use App\Entity\TOperationcab;
use App\Entity\TOperationdet;
use App\Entity\POrganisme;
use App\Controller\ApiController;
use App\Controller\DatatablesController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/preinscription/preinscriptions')]
class PreinscriptionController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'preinscription_index')]
    public function index(Request $request): Response
    {   
        $operations = ApiController::check($this->getUser(), 'preinscription_index', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        return $this->render('preinscription/preinscription.html.twig',[
            'operations' => $operations
        ]);
    }

    #[Route('/list', name: 'preinscription_list')]
    public function list(Request $request): Response
    {
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 ";        
        $columns = array(
            array( 'db' => 'pre.id','dt' => 0 ),
            array( 'db' => 'pre.code','dt' => 1),
            array( 'db' => 'etu.nom','dt' => 2),
            array( 'db' => 'etu.prenom','dt' => 3),
            array( 'db' => 'etab.abreviation','dt' => 4),
            array( 'db' => 'LOWER(form.abreviation)','dt' => 5),
            array( 'db' => 'LOWER(nat.designation)','dt' => 6),
            array( 'db' => 'tbac.designation','dt' => 7),
            array( 'db' => 'etu.moyenne_bac','dt' => 8),
            array( 'db' => 'LOWER(stat.code)','dt' => 9),
            // array( 'db' => 'LOWER(stat.code)','dt' => 10),
            // array( 'db' => 'LOWER(stat.code)','dt' => 11),
            // array( 'db' => 'LOWER(stat.code)','dt' => 12),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                      
         
        FROM `tpreinscription` pre 
        inner join tetudiant etu on etu.id = pre.etudiant_id
        inner join ac_annee an on an.id = pre.annee_id
        inner join ac_formation form on form.id = an.formation_id
        inner join ac_etablissement etab on etab.id = form.etablissement_id
        left join xtype_bac tbac on tbac.id = etu.type_bac_id 
        left join nature_demande nat on nat.id = etu.nature_demande_id 
        inner join pstatut stat on stat.id = pre.statut_id    

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
            $cd = $row['id'];
            // $nestedData[] = $cd;
            $nestedData[] = "<input type ='checkbox' class='cat' id ='$cd' >";
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
}
