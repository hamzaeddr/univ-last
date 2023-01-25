<?php

namespace App\Controller\AdministrationEpreuve;

use App\Controller\ApiController;
use App\Entity\AcAnnee;
use App\Entity\AcEtablissement;
use App\Entity\AcPromotion;
use App\Entity\TInscription;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/administration/impression')]
class ImpressionController extends AbstractController
{
    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }
    #[Route('/', name: 'administration_impression')]
    public function index(Request $request): Response
    {
        $operations = ApiController::check($this->getUser(), 'administration_impression', $this->em, $request);
        if(!$operations) {
            return $this->render("errors/403.html.twig");
        }
        $etablissements =  $this->em->getRepository(AcEtablissement::class)->findBy(['active'=>1]);
        return $this->render('administration_epreuve/impression.html.twig', [
            'operations' => $operations,
            'etablissements' => $etablissements,
        ]);
    }
    #[Route('/list/{promotion}/{salle}', name: 'administration_impression_list')]
    public function list(Request $request, AcPromotion $promotion, $salle): Response
    {
        $order = $request->get('order');
        $annee = $this->em->getRepository(AcAnnee::class)->getActiveAnneeByFormation($promotion->getFormation());
        $inscriptions = $this->em->getRepository(TInscription::class)->getInscriptionsByAnneeAndPromoAndSalle($salle, $annee, $promotion, $order);           
        $html = $this->render("administration_epreuve/pages/list_etudiant.html.twig", [
            'inscriptions' => $inscriptions
        ])->getContent();
        $session = $request->getSession();
        $session->set('inscriptions', $inscriptions);
        return new JsonResponse($html);
    }
    #[Route('/canvas', name: 'administration_impression_canvas')]
    public function canvas(): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'inscription');
        $sheet->setCellValue('B1', 'anonymat');
        $sheet->setCellValue('C1', 'anonymat ratrappage');
        $sheet->setCellValue('D1', 'Numero Salle');
        $sheet->setCellValue('E1', 'Type Salle');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'anonymat_salle.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    #[Route('/import', name: 'administration_impression_import')]
    public function import(Request $request, SluggerInterface $slugger): Response
    {
        $file = $request->files->get('file');
        // dd($file);
        if(!$file){
            return new JsonResponse('Prière d\'importer le fichier',500);
        }
        if($file->guessExtension() !== 'xlsx'){
            return new JsonResponse('Prière d\'enregister un fichier xlsx', 500);            
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'_'.$this->getUser()->getUserIdentifier().'.'.$file->guessExtension();

        // Move the file to the directory where brochures are stored
        try {
            $file->move(
                $this->getParameter('affilier_impression_salle_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception($e);
        }
        $reader = new reader();
        $spreadsheet = $reader->load($this->getParameter('affilier_impression_salle_directory').'/'.$newFilename);
        $worksheet = $spreadsheet->getActiveSheet();
        $spreadSheetArys = $worksheet->toArray();

        unset($spreadSheetArys[0]);
        $sheetCount = count($spreadSheetArys);

        foreach ($spreadSheetArys as $sheet) {
            $inscription = $this->em->getRepository(TInscription::class)->find($sheet[0]);
            $inscription->setCodeAnonymat($sheet[1]);
            $inscription->setCodeAnonymatRat($sheet[2]);
            if($sheet[4] == 0){
                $type = "Salle";
            } else {
                $type = "Zone";
            }
            $inscription->setSalle($type."-".$sheet[3]);
        }
        $this->em->flush();
        return new JsonResponse("Bien Enregistre");

    }
    #[Route('/imprimer', name: 'administration_impression_imprimer')]
    public function imprimer(Request $request)
    {
        $session = $request->getSession();
        $inscriptions = $session->get('inscriptions');
        $html = $this->render("administration_epreuve/pdfs/impression.html.twig", ["inscriptions" => $inscriptions])->getContent();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'margin_left' => '0',
            'margin_right' => '0',
            'margin_top' => '0',
            'margin_bottom' => '0',
            'showBarcodeNumbers' => FALSE
            ]);
       
        $mpdf->WriteHTML($html);
        $mpdf->Output("index_impression.pdf", "I");

    }
    
}
