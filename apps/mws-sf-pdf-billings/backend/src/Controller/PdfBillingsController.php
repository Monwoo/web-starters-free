<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function index(): JsonResponse
    {
        // $this->tcpdf 

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PdfBillingsController.php',
        ]);
    }

    #[Route('/pdf/billings/ID', name: 'app_pdf_billings_preview')]
    public function preview(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PdfBillingsController.php',
        ]);
    }
}
