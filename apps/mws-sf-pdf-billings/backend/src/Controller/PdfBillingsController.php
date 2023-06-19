<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Controller;

use App\Entity\BillingConfig;
use App\Form\BillingConfigSubmitableType;
use App\Repository\BillingConfigRepository;
use App\Services\MwsTCPDF;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Qipsius\TCPDFBundle\Controller\TCPDFController;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;


class PdfBillingsController extends AbstractController
{
    protected TCPDFController $tcpdf;

    public function __construct(TCPDFController $tcpdf) 
    {
        $this->tcpdf = $tcpdf;
        $this->tcpdf->setClassName(MwsTCPDF::class);
    }

    #[Route('/', name: 'app_pdf_billings')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        BillingConfigRepository $bConfigRepository,
        LoggerInterface $logger,
    ): Response
    {
        // $clientId = $request->get('clientId');

        // https://symfony.com/doc/current/form/data_mappers.html
        // https://symfony.com/doc/current/form/use_empty_data.html
        // https://symfony.com/doc/current/form/direct_submit.html
        $bConfigFactory = function () {
            $bConfig = new BillingConfig();
            // Default empty client, to let space for end client fill all his fields
             // TODO : Setting 'null' from form give error : Expected argument of type "string", "null" given at property path "clientName"...
            $bConfig->setClientName('______________________________');
            $bConfig->setClientSlug('--');
            return $bConfig;
        };

        $bConfig = $bConfigRepository->findOneBy([]) ?? $bConfigFactory();
        // var_dump($bConfig);exit;
        // $logger->info('From route [app_pdf_billings] :' . json_encode(get_object_vars($bConfig), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $logger->info('From route [app_pdf_billings] :' . json_encode($bConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $csrfToken = $request->request->get('_token');

        if ($csrfToken && !$this->isCsrfTokenValid('pdf-billings', $csrfToken)) {
            $logger->error('WRONG CSRF token', [
                'token' => $csrfToken,
            ] );

            return $this->json([ 
                'error' => 'Wrong initial call!',
            ]);    
        }
        // https://symfony.com/doc/current/reference/forms/types/submit.html
        // https://symfony.com/doc/current/forms.html
        // https://symfony.com/doc/current/form/create_form_type_extension.html
        $form = $this->createForm(BillingConfigSubmitableType::class, $bConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $bConfig->setComputedValue(...);
            $em->persist($bConfig);
            $em->flush();

            // TIPS : un-comment below if you want to redirect to full PDF view at form submit
            // return $this->redirectToRoute('app_pdf_billings_view', [], Response::HTTP_SEE_OTHER);
        }

        // return $this->render('pdf-billings/index.html.twig', [
        //     'form' => $form->createView(),
        return $this->renderForm('pdf-billings/index.html.twig', [
            'bConfig' => $bConfig,
            'form' => $form,
            'title' => 'MWS SF PDF Billings - Index'
        ]);
    }

    #[Route('/view', name: 'app_pdf_billings_view')]
    public function view(
        // EngineInterface $tplEngine,
        string $projectDir,
        BillingConfigRepository $bConfigRepository,
    ) : Response {
        // Missing deps injections in new version ?
        // Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $tplEngine ?
        // $html = $tplEngine->render('pdf-billings/pdf-views/monwoo-quotation.html.twig', [

        $twig = $this->container->get('twig');

        $bConfig = $bConfigRepository->findOneBy([]);
        $pdf = $this->tcpdf->create();

        // 🇺🇸🇺🇸 SEO
        $pdf->SetAuthor('Miguel Monwoo (service@monwoo.com)');
        $pdf->SetTitle('Devis 000');
        $pdf->SetSubject('Devis Monwoo');
        $pdf->SetKeywords('Monwoo, PWA, Svelte, PHP, Symfony');

        // 🇺🇸🇺🇸 Global page container
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // https://stackoverflow.com/questions/5503969/tcpdf-how-to-adjust-height-of-header
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP-10, PDF_MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM-10);

        $pdf->setFontSubsetting(true);
        // $pdf->SetFont('dejavusans', '', 14, '', true);
        // $pdf->SetFont('roboto', '', 14, '', true);
        // set text shadow effect
        $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>0.7, 'blend_mode'=>'Normal'));

        // https://github.com/tecnickcom/TCPDF/blob/main/examples/example_009.php
        // $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // // set JPEG quality
        // $pdf->setJPEGQuality(75);
        // $pdf->Image('images/image_demo.jpg', 15, 140, 75, 113, 'JPG', 'http://www.tcpdf.org', '', true, 150, '', false, false, 1, false, false, false);

        // 🇺🇸🇺🇸 Header arrangements
        $PDF_HEADER_LOGO = null; // "logo.png";//any image file. check correct path.
        $PDF_HEADER_LOGO_WIDTH = 0; // "20";
        $PDF_HEADER_TITLE = null;
        $PDF_HEADER_STRING = "Monwoo"
        . "                                                                             "
        . "                             Devis n°" . $bConfig->getQuotationNumber();
        // $PDF_HEADER_STRING = "Tel 1234567896 Fax 987654321\n"
        // . "E abc@gmail.com\n"
        // . "www.abc.com";
        // $PDF_HEADER_STRING = "";// "Devis n°" . $bConfig->getQuotationNumber();
        $pdf->SetHeaderData(
            $PDF_HEADER_LOGO, $PDF_HEADER_LOGO_WIDTH, $PDF_HEADER_TITLE, $PDF_HEADER_STRING,
            [0,0,0], [200,200,200]
        );
        // https://stackoverflow.com/questions/25934841/tcpdf-how-to-set-top-margin-in-header
        // $margin = $pdf->getMargins();
        // $pdf->SetY($margin['top']);


        // 🇺🇸🇺🇸 Footer arrangements
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // 🇺🇸🇺🇸 BODY arrangements
        $pdf->AddPage();
        // Set some content to print
        $html = $twig->render('pdf-billings/pdf-views/monwoo-quotation.html.twig', [
            'billingConfig' => $bConfig,
            'pdfCssStyles' => file_get_contents($projectDir . '/public/pdf-views/theme.css'),
        ]);

        // https://stackoverflow.com/questions/14495688/how-to-put-html-data-into-header-of-tcpdf
        // $pdf->writeHTMLCell( // NOP, DO not go OVER header barre...
        //     $w = 0, $h = 0, $x = -1, $y = '',
        //     $leftHeader, $border = 0, $ln = 1, $fill = 0,
        //     $reseth = true, $align = 'top', $autopadding = true);

        // Print text using writeHTMLCell()
        // $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->lastPage();
        $response = new Response($pdf->Output('MonwooQuotation_000.pdf'));
        $response->headers->set('Content-Type', 'application/pdf');
        return $response;
    }

    #[Route('/pdf/billings/ID', name: 'app_pdf_billings_preview')]
    public function preview(): JsonResponse
    {
        return $this->json([ // TODO : not needed for simple version ? view is enough...
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PdfBillingsController.php',
        ]);
    }
}
