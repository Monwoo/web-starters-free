<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Qipsius\TCPDFBundle\Controller\TCPDFController;

class PdfBillingsController extends AbstractController
{
    protected TCPDFController $tcpdf;

    public function __construct(TCPDFController $tcpdf) 
    {
        $this->tcpdf = $tcpdf;
    }

    #[Route('/', name: 'app_pdf_billings')]
    public function index(): Response
    {

        return $this->render('pdf-billings/index.html.twig', [
            'title' => 'Capacités'
        ]);
    }

    #[Route('/view', name: 'app_pdf_billings_view')]
    public function view() : Response {
        $pdf = $this->tcpdf->create();

        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->SetAuthor('Miguel Monwoo (service@monwoo.com)');
        $pdf->SetTitle('Devis 000');
        $pdf->SetSubject('Devis Monwoo');
        $pdf->SetKeywords('Monwoo, PWA, Svelte, PHP, Symfony');

        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 14, '', true);
        // set text shadow effect
        $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

        // Set some content to print
        $html = <<<EOD
        // Welcome to mws-sf-pdf-billings
        // EOD;
        <h1>Welcome to <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a>!</h1>
        <i>This is the first example of TCPDF library.</i>
        <p>This text is printed using the <i>writeHTMLCell()</i> method but you can also use: <i>Multicell(), writeHTML(), Write(), Cell() and Text()</i>.</p>
        <p>Please check the source code documentation and other examples for further information.</p>
        <p style="color:#CC0000;">TO IMPROVE AND EXPAND TCPDF I NEED YOUR SUPPORT, PLEASE <a href="http://sourceforge.net/donate/index.php?group_id=128076">MAKE A DONATION!</a></p>
        EOD;

        $pdf->AddPage();
        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

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
