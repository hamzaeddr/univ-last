<?php

namespace App\Controller\Etudiant;

use App\Entity\TAdmission;
use App\Entity\TInscription;
use App\Controller\ApiController;
use App\Entity\AcSemestre;
use App\Entity\ExMnotes;
use App\Entity\ExSnotes;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/etudiant/rechercheavance')]
class RechercheAvanceController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'etudiant_recherche_avance')]
    public function index(Request $request): Response
    {
        //check if user has access to this page
        $operations = ApiController::check($this->getUser(), 'etudiant_recherche_avance', $this->em, $request);
        // dd($operations);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        // $admissions = $this->em->getRepository(TAdmission::class)->findAll();
        return $this->render('etudiant/recherche_avance/index.html.twig', [
            'operations' => $operations
        ]);
    }
    #[Route('/find', name: 'etudiant_recherche_avance_find')]
    public function find(Request $request): Response
    {
        $admissions = $this->em->getRepository(TAdmission::class)->findAdmssions($request->query->get("search"));
        
        // dd($admissions);
        return new Response(json_encode($admissions));
    }
    #[Route('/findAnnee/{admission}', name: 'etudiant_recherche_avance_findannee')]
    public function findAnnee(TAdmission $admission): Response
    {
        $html = "<option value=''>Choix année</option>";
        // dd($admission->getInscriptions());
        foreach ($admission->getInscriptions() as $inscription) {
            $html .= "<option value='".$inscription->getId()."'>".$inscription->getAnnee()->getDesignation()."</option>";
        }
        return new JsonResponse($html);
    }
    #[Route('/recherche/{inscription}', name: 'etudiant_recherche_avance_recherche')]
    public function recherche(TInscription $inscription): Response
    {
        $administratif = $this->render("etudiant/recherche_avance/page/administratif.html.twig", ['inscription' => $inscription])->getContent();
        $academique = $this->render("etudiant/recherche_avance/page/academique.html.twig", ['inscription' => $inscription])->getContent();        
        $informations = $this->render("etudiant/recherche_avance/page/informations.html.twig", ['inscription' => $inscription])->getContent();
        // dd($informations);
        
        return new JsonResponse([
            'informations' => $informations,
            'administratif' => $administratif,
            'academique' => $academique
        ]);
    }
    #[Route('/attestation/inscription/{inscription}', name: 'etudiant_recherche_attestation_inscription')]
    public function attestationInscription(TInscription $inscription)
    {
        $html = $this->render("etudiant/recherche_avance/pdf/attestations/inscription.html.twig", [
            'inscription' => $inscription
        ])->getContent();
        // dd($html);
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 
            'margin_left' => 5,
            'margin_right' => 5,
        ]);
        // $mpdf->SetHTMLHeader(
        //     $this->render("etudiant/recherche_avance/pdf/attestations/header.html.twig")->getContent()
        // );
        $mpdf->SetHTMLFooter(
            $this->render("etudiant/recherche_avance/pdf/attestations/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        
        $mpdf->Output("attestaion.pdf", "I");
        
    }
    #[Route('/attestation/scolarite/{inscription}', name: 'etudiant_recherche_attestation_scolarite')]
    public function attestationScolarite(TInscription $inscription): Response
    {
       
        $html = $this->render("etudiant/recherche_avance/pdf/attestations/scolarite.html.twig", [
            'inscription' => $inscription
        ])->getContent();
        // dd($html);
        $mpdf = new Mpdf([
            'margin_left' => 5,
            'margin_right' => 5,
        ]);
        // $mpdf->SetHTMLHeader(
        //     $this->render("etudiant/recherche_avance/pdf/attestations/header.html.twig")->getContent()
        // );
        $mpdf->SetHTMLFooter(
            $this->render("etudiant/recherche_avance/pdf/attestations/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        
        $mpdf->Output("scolarite.pdf", "I");
    }
    
    #[Route('/attestation/reussite/{inscription}', name: 'etudiant_recherche_attestation_reussite')]
    public function attestationReussite(TInscription $inscription): Response
    {
        $prm = $this->em->getRepository(TInscription::class)->findBy([
            'admission' => $inscription->getAdmission(),
            'promotion' => $inscription->getPromotion()
        ]);
        if (count($prm) > 1) {
            return new JsonResponse('Redoublant!!',500);
        }
        $html = $this->render("etudiant/recherche_avance/pdf/attestations/reussite.html.twig", [
            'inscription' => $inscription
        ])->getContent();
        // dd($html);
        $mpdf = new Mpdf([
            'margin_left' => 5,
            'margin_right' => 5,
        ]);
        // $mpdf->SetHTMLHeader(
        //     $this->render("etudiant/recherche_avance/pdf/attestations/header.html.twig")->getContent()
        // );
        $mpdf->SetHTMLFooter(
            $this->render("etudiant/recherche_avance/pdf/attestations/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        
        $mpdf->Output("reussite.pdf", "I");
    }

    #[Route('/attestation/reussitenote/{inscription}', name: 'etudiant_recherche_attestation_reussite_note')]
    public function attestationReussiteNote(TInscription $inscription): Response
    {
        if ($inscription->getAnotes() == null) {
            die('Notes Inrouvable!!');
        }
        $html = $this->render("etudiant/recherche_avance/pdf/attestations/reussite_avec_moyenne.html.twig", [
            'inscription' => $inscription
        ])->getContent();
        // dd($html);
        $mpdf = new Mpdf([
            'margin_left' => 5,
            'margin_right' => 5,
        ]);
        // $mpdf->SetHTMLHeader(
        //     $this->render("etudiant/recherche_avance/pdf/attestations/header.html.twig")->getContent()
        // );
        $mpdf->SetHTMLFooter(
            $this->render("etudiant/recherche_avance/pdf/attestations/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        
        $mpdf->Output("reussite.pdf", "I");
       
        
    }
    #[Route('/attestation/releve/module/{inscription}/{semestre}/{assiduite}', name: 'etudiant_recherche_releve_module')]
    public function attestationReleveModule(TInscription $inscription, AcSemestre $semestre, $assiduite): Response
    {
        if($assiduite == 0){
            $noteModulesBySemestre = $this->em->getRepository(ExMnotes::class)->getNotesModuleSansAssiduiteBySemestre($semestre, $inscription);
        } else {
            $noteModulesBySemestre = $this->em->getRepository(ExMnotes::class)->getNotesModuleBySemestre($semestre, $inscription);
        }
        $noteSemestre = $this->em->getRepository(ExSnotes::class)->findOneBy(['semestre' => $semestre, 'inscription' => $inscription]);
        $html = $this->render("etudiant/recherche_avance/pdf/academique/note_module.html.twig", [
            'inscription' => $inscription,
            'noteModulesBySemestre' => $noteModulesBySemestre,
            'semestre' => $semestre,
            'assiduite' => $assiduite,
            'noteSemestre' => $noteSemestre
        ])->getContent();
        // dd($html);
        $mpdf = new Mpdf([
            'margin_left' => 5,
            'margin_right' => 5,
        ]);
        $mpdf->SetHTMLHeader(
            $this->render("etudiant/recherche_avance/pdf/academique/header.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("etudiant/recherche_avance/pdf/academique/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        
        $mpdf->Output("releve_module.pdf", "I");
       
      
    }
    #[Route('/attestation/releve/annee/{inscription}/{assiduite}', name: 'etudiant_recherche_releve_annee')]
    public function attestationReleveAnnee(TInscription $inscription, $assiduite): Response
    {
        $semestres = $this->em->getRepository(ExSnotes::class)->findBy(['inscription' => $inscription]);
        $noteSemestre1 = $semestres ? $semestres[0] : die("Note semestre introuvable");
        $noteSemestre2 = $semestres ? $semestres[1] : die("Note semestre introuvable");
        if($assiduite == 0){
            $noteModulesBySemestre1 = $this->em->getRepository(ExMnotes::class)->getNotesModuleSansAssiduiteBySemestre($noteSemestre1->getSemestre(), $inscription);
            $noteModulesBySemestre2 = $this->em->getRepository(ExMnotes::class)->getNotesModuleSansAssiduiteBySemestre($noteSemestre2->getSemestre(), $inscription);
        } else {
            $noteModulesBySemestre1 = $this->em->getRepository(ExMnotes::class)->getNotesModuleBySemestre($noteSemestre1->getSemestre(), $inscription);
            $noteModulesBySemestre2 = $this->em->getRepository(ExMnotes::class)->getNotesModuleBySemestre($noteSemestre2->getSemestre(), $inscription);
        }
        $html = $this->render("etudiant/recherche_avance/pdf/academique/note_annee.html.twig", [
            'inscription' => $inscription,
            'noteModulesBySemestre1' => $noteModulesBySemestre1,
            'noteModulesBySemestre2' => $noteModulesBySemestre2,
            'noteSemestre1' => $noteSemestre1,
            'noteSemestre2' => $noteSemestre2,
            'assiduite' => $assiduite
        ])->getContent();
        // dd($html);
        $mpdf = new Mpdf([
            'margin_left' => 5,
            'margin_right' => 5,
        ]);
        $mpdf->SetHTMLHeader(
            $this->render("etudiant/recherche_avance/pdf/academique/header.html.twig")->getContent()
        );
        $mpdf->SetHTMLFooter(
            $this->render("etudiant/recherche_avance/pdf/academique/footer.html.twig")->getContent()
        );
        $mpdf->WriteHTML($html);
        
        $mpdf->Output("releve_annee.pdf", "I");
       
      
    }
}
