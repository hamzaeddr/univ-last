<?php

namespace App\Controller;

use App\Entity\UsModule;
use App\Entity\UsSousModule;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{   
    /**
     * @Route("/", name="index")
     */
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        if(in_array('ROLE_ADMIN', $this->getUser()->getRoles())){
            $sousModules = $doctrine->getManager()->getRepository(UsSousModule::class)->findBy([],['ordre'=>'ASC']);
        } else {
            $sousModules = $doctrine->getManager()->getRepository(UsSousModule::class)->findByUserOperations($this->getUser());
        }
        $modules = $doctrine->getManager()->getRepository(UsModule::class)->getModuleBySousModule($sousModules);
        $data = [];
        // dd($sousModules);
        foreach($modules as $module) {
            $sousModuleArray = [];
            foreach ($sousModules as $sousModule) {
                if($sousModule->getModule()->getId() == $module->getId()) {
                    // dd($sousModule);
                    array_push($sousModuleArray,$sousModule);
                }
            }
            array_push($data, [
                'module' => $module,
                'sousModule' => $sousModuleArray
            ]);
            
        }
        // dd($data);
        $request->getSession()->set('modules', $data);
        if(count($sousModules) < 1) {
            die("Vous n'avez aucun prévilege pour continue cette operation. veuillez contacter votre chef !");
        }
        return $this->redirectToRoute($sousModules[0]->getLink());
    }
}
