<?php

namespace App\Controller\Parametre;

use App\Controller\DatatablesController;
use App\Entity\User;
use App\Entity\UsModule;
use App\Entity\UsOperation;
use App\Entity\UsSousModule;
use Doctrine\Persistence\ManagerRegistry;
use ProxyManager\Factory\RemoteObject\Adapter\JsonRpc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parametre/user')]
class UserController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'parametre_user')]
    public function index(): Response
    {
        $modules = $this->em->getRepository(UsModule::class)->findAll();
        return $this->render('parametre/user/index.html.twig', [
            'modules' => $modules
        ]);
    }
    #[Route('/list', name: 'parametre_user_list')]
    public function gestionInscriptionList(Request $request): Response
    {
        
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        $filtre = "where 1 = 1";   
        // dd($params->all('columns')[0]);
            
        $columns = array(
            array( 'db' => 'u.id','dt' => 0),
            array( 'db' => 'u.username','dt' => 1),
            array( 'db' => 'u.nom','dt' => 2),
            array( 'db' => 'u.prenom','dt' => 3),
            array( 'db' => 'u.roles','dt' => 4),
            array( 'db' => 'u.enable','dt' => 5),

        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        
        FROM users u 
        
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
                if($key == 5) {
                    $nestedData[] = $value == 1 ?  "<i class='fas fa-lock-open disable text-success' id='$cd'></i>" : "<i class='enable fas fa-lock text-danger' id='$cd'></i>";
                    $nestedData[] = "<button class='btn_reinitialiser btn btn-secondary' id='$cd'><i class='fas fa-sync'></i></button>";
                }
                if($key == 4) {
                    
                    $nestedData[] = implode(",", $this->em->getRepository(User::class)->find($cd)->getRoles());
                } else {
                    $nestedData[] = $value;
                }
            }
            $nestedData["DT_RowId"] = $cd;
            // $nestedData["DT_RowClass"] = $cd;
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
    #[Route('/getoperations/{user}', name: 'parametre_user_operations')]
    public function operations(User $user): Response
    {
       $ids = [];
       foreach ($user->getOperations() as $operation) {
           array_push($ids, ["id" => $operation->getId()]);
       }

       return new JsonResponse($ids);
    }
    #[Route('/all/{user}/{type}', name: 'parametre_user_all')]
    public function all(User $user, $type): Response
    {
        $operations = $this->em->getRepository(UsOperation::class)->findAll();
        if($type === "add") {
            foreach ($operations as $operation) {
                $user->addOperation($operation);
            }
        } else if($type==="remove") {
            foreach ($operations as $operation) {
                $user->removeOperation($operation);
            }
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/sousmodule/{sousModule}/{user}/{type}', name: 'parametre_user_sousmodule')]
    public function sousmodule(UsSousModule $sousModule, User $user, $type): Response
    {
        if($type === "add") {
            foreach ($sousModule->getOperations() as $operation) {
                $user->addOperation($operation);
            }
        } else if($type==="remove") {
            foreach ($sousModule->getOperations() as $operation) {
                $user->removeOperation($operation);
            }
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/module/{module}/{user}/{type}', name: 'parametre_user_module')]
    public function module(UsModule $module, User $user, $type): Response
    {
        if($type === "add") {
            foreach ($module->getSousModule() as $sousModule) {
                foreach ($sousModule->getOperations() as $operation) {
                    $user->addOperation($operation);
                }
            }
        } else if($type==="remove") {
            foreach ($module->getSousModule() as $sousModule) {
                foreach ($sousModule->getOperations() as $operation) {
                    $user->removeOperation($operation);
                }
            }
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/operation/{operation}/{user}/{type}', name: 'parametre_user_operation')]
    public function operation(UsOperation $operation, User $user, $type): Response
    {
        if($type === "add") {
            $user->addOperation($operation);
        } else if($type==="remove") {
            $user->removeOperation($operation);
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/active/{user}/{type}', name: 'parametre_user_active')]
    public function active(User $user, $type): Response
    {
        $user->setEnable($type);
        $this->em->flush();
        return new JsonResponse(1);
    }
    // private UserPasswordHasherInterface $passwordEncoder;
    // #[Route('/reinitialiser/{user}', name: 'parametre_user_reinitialiser')]
    // public function reinitialiser(Request $request,User $user, UserPasswordHasherInterface $passwordHasher,UserPasswordHasherInterface $passwordEncoder)
    // {
    //     // dd($user);
    //     $this->passwordEncoder = $passwordEncoder;
    //     $user->setPassword($passwordHasher->hashPassword(
    //         $user,
    //         '0123456789'
    //     ));
    //     $this->em->flush();
    //     dd($user);
    //     return new JsonResponse('Mot De Passe Bien Réinitialiser',200);
    // }
}

