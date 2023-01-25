<?php

namespace App\Controller\Admission;

use DateTime;
use App\Controller\ApiController;
use App\Entity\TAdmission;
use App\Entity\TPreinscription;
use App\Controller\DatatablesController;
use App\Entity\PStatut;
use App\Entity\TOperationcab;
use App\Entity\POrganisme;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/admission/admissions')]

class AdmissionController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'admission_index')]
    public function index(Request $request): Response
    {   

        $organismes = $this->em->getRepository(POrganisme::class)->findAll();
        $operations = ApiController::check($this->getUser(), 'admission_index', $this->em, $request);

        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $organismes = $this->em->getRepository(POrganisme::class)->findAll();
        return $this->render('admission/admissions.html.twig', [
            'operations' => $operations,
            'organismes' => $organismes
        ]);
    }
    #[Route('/candidat_addmissible_list', name: 'candidat_admissible_list')]
    public function candidatAddmissibleList(Request $request): Response
    {
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1 and";       
        $columns = array(
            array( 'db' => 'pre.code','dt' => 0),
            array( 'db' => 'etu.nom','dt' => 1),
            array( 'db' => 'etu.prenom','dt' => 2),
            array( 'db' => 'etab.abreviation','dt' => 3),
            array( 'db' => 'UPPER(form.abreviation)','dt' => 4),
            array( 'db' => 'nd.designation','dt' => 5),
            array( 'db' => 'etu.moyenne_bac','dt' => 6),
            array( 'db' => 'pre.rang_p','dt' => 7),
            array( 'db' => 'pre.rang_s','dt' => 8),
            array( 'db' => 'nd.concours','dt' => 9),
            array( 'db' => 'UPPER(st.designation)','dt' => 10),
            array( 'db' => 'UPPER(st2.designation)','dt' => 11),
            array( 'db' => 'pre.id','dt' => 12),

        );

        $filtre .= " adm.id is null and st2.table0 = 'preinscription' AND st2.phase0 = 'admission' and st2.visible_admission = '1' and st2.visible = '1' ";
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                      
                FROM tpreinscription pre
                inner join tetudiant etu on etu.id = pre.etudiant_id
                inner join ac_annee an on an.id = pre.annee_id
                inner join ac_formation form on form.id = an.formation_id              
                inner join ac_etablissement etab on etab.id = form.etablissement_id 
                LEFT JOIN nature_demande nd ON etu.nature_demande_id = nd.id
                INNER JOIN pstatut st ON st.id = pre.categorie_liste_id
                LEFT JOIN pstatut st2 ON st2.id = pre.admission_liste_id
                LEFT JOIN tadmission adm on adm.preinscription_id = pre.id
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
        $changed_column = $params->all('order')[0]['column'] > 0 ? $params->all('order')[0]['column'] - 1 : 0;
        $sqlRequest .= " ORDER BY " .DatatablesController::Pluck($columns, 'db')[$changed_column] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";
        // $sqlRequest .= DatatablesController::Order($request, $columns);
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
            $nestedData[] = "<input type ='checkbox' class='check_admissible' id ='$cd' >";
            $nestedData[] = $cd;
            // dd($row);

            foreach (array_values($row) as $key => $value) {
                if($key == 9) {
                    $nestedData[] = $value == 1 ? 'Avec Concours' : 'Sans Concours';
                }
                else if($key < 12) {
                    $nestedData[] = $value;
                }
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
    #[Route('/candidat_admis_list', name: 'candidat_admis_list')]
    public function candidatAdmisList(Request $request): Response
    {
        $params = $request->query;
        $where = $totalRows = $sqlRequest = "";
        $filtre = " where 1 = 1 ";       
        $columns = array(
            array( 'db' => 'ad.id','dt' => 0),
            array( 'db' => 'ad.code','dt' => 1),
            array( 'db' => 'UPPER(pre.code)','dt' => 2),
            array( 'db' => 'etu.nom','dt' => 3),
            array( 'db' => 'etu.prenom','dt' => 4),
            array( 'db' => 'etab.abreviation','dt' => 5),
            array( 'db' => 'UPPER(form.abreviation)','dt' => 6),
            array( 'db' => 'nd.designation','dt' => 7),

            // array( 'db' => 'ad.code','dt' => 0),
            // array( 'db' => 'UPPER(pre.code)','dt' => 1),
            // array( 'db' => 'etu.nom','dt' => 2),
            // array( 'db' => 'etu.prenom','dt' => 3),
            // array( 'db' => 'etab.abreviation','dt' => 4),
            // array( 'db' => 'UPPER(form.abreviation)','dt' => 5),
            // array( 'db' => 'nd.designation','dt' => 6),
            // array( 'db' => 'ad.id','dt' => 7)

        );

        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
                      
                FROM tadmission ad
                inner join tpreinscription pre on pre.id = ad.preinscription_id
                inner join tetudiant etu on etu.id = pre.etudiant_id
                inner join ac_annee an on an.id = pre.annee_id
                inner join ac_formation form on form.id = an.formation_id              
                inner join ac_etablissement etab on etab.id = form.etablissement_id 
                LEFT JOIN nature_demande nd ON etu.nature_demande_id = nd.id 
                $filtre"
        ;
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
        
        // dd($params->all('order')[0]['column']);
        $changed_column = $params->all('order')[0]['column'] > 0 ? $params->all('order')[0]['column'] - 1 : 0;
        $sqlRequest .= " ORDER BY " .DatatablesController::Pluck($columns, 'db')[$changed_column] . "   " . $params->all('order')[0]['dir'] . "  LIMIT " . $params->get('start') . " ," . $params->get('length') . " ";
        // $sqlRequest .= DatatablesController::Order($request, $columns);
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
            $nestedData[] = "<input type ='checkbox' class='check_admissible' id ='$cd' >";
            // $nestedData[] = $cd;
            // dd($row);

            foreach (array_values($row) as $key => $value) {
                // if($key < 0) {
                    $nestedData[] = $value;
                // }
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
    #[Route('/new', name: 'admission_new')]
    public function admissionNew(Request $request): Response
    {
        // dd($request);
        $ids = json_decode($request->get('idpreins'));
        foreach ($ids as $id) {
            $preinscription = $this->em->getRepository(TPreinscription::class)->find($id);
            if(count($preinscription->getAdmissions()) == 0) {
                $admission = new TAdmission();
                $admission->setPreinscription($preinscription);
                $admission->setUserCreated($this->getUser());
                $admission->setStatut(
                    $this->em->getRepository(PStatut::class)->find(7)
                );
                $admission->setCreated(new \DateTime('now'));
                $this->em->persist($admission);
                $this->em->flush();
                $formation = $preinscription->getAnnee()->getFormation()->getAbreviation();
                $etablissement = $preinscription->getAnnee()->getFormation()->getEtablissement()->getAbreviation();
                $admission->setCode('ADM-'.$etablissement.'_'.$formation.str_pad($admission->getId(), 8, '0', STR_PAD_LEFT));
                $this->em->flush();
                
                // $operationcab = new TOperationcab();
                // $operationcab->setPreinscription($preinscription);
                // $operationcab->setAnnee($preinscription->getAnnee());
                // $operationcab->setOrganisme($this->em->getRepository(POrganisme::class)->find(7));
                // $operationcab->setCategorie('admission');
                // $operationcab->setCreated(new DateTime('now'));
                // $operationcab->setUserCreated($this->getUser());
                // $operationcab->setActive(1);
                // $this->em->persist($operationcab);
                // $this->em->flush();
                // $etab = $preinscription->getAnnee()->getFormation()->getEtablissement()->getAbreviation();
                // $operationcab->setCode($etab.'-FAC'.str_pad($operationcab->getId(), 8, '0', STR_PAD_LEFT).'/'.date('Y'));
                // $this->em->flush();
            }
        }

        return new JsonResponse('Admission bien enregister', 200);
    }
    #[Route('/annuler', name: 'admission_annuler')]
    public function admissionAnnuler(Request $request): Response
    {
        $ids = json_decode($request->get('idAdmissions'));
        foreach ($ids as $id) {
            $admission = $this->em->getRepository(TAdmission::class)->find($id);
            if(count($admission->getInscriptions()) > 0) {
                return new JsonResponse(['error' => $admission->getCode() . 'déja inscrit'] , 500);
            }
            $this->em->remove($admission);
            $this->em->flush();
        }
        return new JsonResponse('Admissions bien annuler', 200);
    }
}
