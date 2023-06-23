<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Controller;

use App\Entity\BillingConfig;
use App\Form\BillingConfigSubmitableType;
use App\Repository\BillingConfigRepository;
use App\Services\MwsTCPDF;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Qipsius\TCPDFBundle\Controller\TCPDFController;
use Doctrine\ORM\EntityManagerInterface;
use Locale;
use Psr\Log\LoggerInterface;


class PdfBillingsController extends AbstractController
{
    protected TCPDFController $tcpdf;
    protected $billingConfigFactory;
    protected $em;
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        TCPDFController $tcpdf,
        EntityManagerInterface $em
    ) {
        ob_start();

        $this->logger = $logger;
        $this->tcpdf = $tcpdf;
        $this->em = $em;
        $this->tcpdf->setClassName(MwsTCPDF::class);

        // https://symfony.com/doc/current/form/data_mappers.html
        // https://symfony.com/doc/current/form/use_empty_data.html
        // https://symfony.com/doc/current/form/direct_submit.html
        $this->billingConfigFactory = function ($slug = null) use ($em) {
            $bConfig = new BillingConfig();
            // Default empty client, to let space for end client fill all his fields
            // TODO : Setting 'null' from form give error : Expected argument of type "string", "null" given at property path "clientName"...
            $bConfig->setClientName('______________________________');
            $bConfig->setClientSlug($slug ?? '--');
            $bConfig->setQuotationNumber('________________');
            $bConfig->setClientEmail('______________________________');
            $bConfig->setClientTel('______________________________');
            $bConfig->setClientSIRET('______________________________');
            $bConfig->setClientTvaIntracom('______________________________');
            $bConfig->setClientAddressL1('______________________________');
            $bConfig->setClientAddressL2('______________________________');
            $bConfig->setClientWebsite('______________________________');
            $bConfig->setClientLogoUrl('______________________________');
            $bConfig->setClientName('______________________________');
            $bConfig->setClientName('______________________________');
            $timable = new DateTime();

            $bConfig->setQuotationStartDay($timable);
            // $timable->add(new DateInterval("PT24H"));
            $timable->add(new DateInterval("P15D"));

            $bConfig->setQuotationEndDay($timable);

            $em->persist($bConfig);
            $em->flush();
            ob_end_clean();

            return $bConfig;
        };
    }

    #[Route('/', name: 'app_pdf_billings')]
    public function index(
        Request $request,
        BillingConfigRepository $bConfigRepository
    ): Response {
        // $clientId = $request->get('clientId');

        // https://stackoverflow.com/questions/21124450/how-to-use-curl-multipart-form-data-to-post-array-field-from-command-line

        // $rawBillingConfig = $request->request->get('billing_config_submitable'); // To read from POST ONLY
        $rawBillingConfig = $request->get('billing_config_submitable'); // This way : will LOAD in GET, set in POST request ;)
        $clientSlug = $rawBillingConfig
            ? ($rawBillingConfig['clientSlug'] ?? null) : null;
        $clientSlug = $clientSlug ? $clientSlug : '--';
        // var_dump($clientSlug); exit;

        $bConfig = $bConfigRepository->findOneBy([
            'clientSlug' => $clientSlug, // Default empty client, all fillable by hand version...
        ]) ?? ($this->billingConfigFactory)($clientSlug);
        // var_dump($bConfig);exit;
        // $this->logger->info('From route [app_pdf_billings] :' . json_encode(get_object_vars($bConfig), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $this->logger->info('From route [app_pdf_billings] :' . json_encode($bConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // $csrfToken = $request->request->get('_token');
        // if ($csrfToken && !$this->isCsrfTokenValid('pdf-billings', $csrfToken)) {
        //     $this->logger->error('WRONG CSRF token', [
        //         'token' => $csrfToken,
        //     ]);
        //     return $this->json([
        //         'error' => 'Wrong initial call!',
        //     ]);
        // }

        // https://symfony.com/doc/current/reference/forms/types/submit.html
        // https://symfony.com/doc/current/forms.html
        // https://symfony.com/doc/current/form/create_form_type_extension.html
        $form = $this->createForm(BillingConfigSubmitableType::class, $bConfig);
        $form->handleRequest($request);
        // var_dump($form->isSubmitted());var_dump($form->isValid()); exit;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // var_dump($bConfig); exit;
                // $bConfig->setComputedValue(...);
                $this->em->persist($bConfig);
                $this->em->flush();

                // TIPS : un-comment below if you want to redirect to full PDF view at form submit
                // return $this->redirectToRoute('app_pdf_billings_view', [], Response::HTTP_SEE_OTHER);
            } else {
                // var_dump($form->getErrors(true)->__toString());exit;
                $this->logger->error(
                    'Form submit errors : '
                        . $form->getErrors(true)->__toString()
                );
            }
        }
        ob_end_clean();

        // return $this->render('pdf-billings/index.html.twig', [
        //     'form' => $form->createView(),
        return $this->renderForm('pdf-billings/index.html.twig', [
            'billingConfig' => $bConfig,
            'form' => $form,
            'title' => 'MWS SF PDF Billings - Index'
        ]);
    }

    // https://symfony.com/doc/current/routing.html
    #[Route(
        '/view/{clientSlug}/{template}',
        defaults: ['clientSlug' => 1, 'template' => 'monwoo'],
        name: 'app_pdf_billings_view'
    )]
    public function view(
        string $clientSlug,
        string $template,
        // EngineInterface $tplEngine,
        string $projectDir,
        BillingConfigRepository $bConfigRepository
    ): Response {
        // Missing deps injections in new version ?
        // Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $tplEngine ?
        // $html = $tplEngine->render('pdf-billings/pdf-views/monwoo-quotation.html.twig', [
        ob_start();

        $twig = $this->container->get('twig');

        $bConfig = $bConfigRepository->findOneBy([
            'clientSlug' => $clientSlug, // Default empty client, all fillable by hand version...
        ]) ?? $bConfigRepository->findOneBy([
            'clientSlug' => '--', // Default empty client, all fillable by hand version...
        ]) ?? ($this->billingConfigFactory)();

        /**
         * @var MwsTCPDF $pdf
         */
        $pdf = $this->tcpdf->create();

        /*
        https://stackoverflow.com/questions/50621578/get-version-from-composer-json-symfony
        use PackageVersions\Versions;
        use Jean85\PrettyVersions;
        // Will output "1.0.0@0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33"
        echo Versions::getVersion('myvendor/mypackage');
        // Will output "1.0.0"
        echo (string) PrettyVersions::getVersion('myvendor/mypackage');
        */

        // Ok, but need local file access readings...
        // $composerJson = file_get_contents(
        //     $projectDir . '/composer.json'
        // );
        // $rootPackage = json_decode($composerJson, true);

        // $version = \Composer\InstalledVersions::getRootPackage()['version'];
        // ok, but need 'composer install' run to refresh right version :
        $rootPackage = \Composer\InstalledVersions::getRootPackage();

        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];
        $packageName = array_slice(explode("monwoo/", $rootPackage['name']), -1)[0];
        $isDev = $rootPackage['dev'] ?? false;
        // var_dump($rootPackage);exit;
        if ($isDev) {
            $this->logger->warning('Packages are installed for DEV environement', $rootPackage);
        }
        $templatePath = "pdf-billings/pdf-views/quotation-templates/$template.html.twig";
        // var_dump(get_class_methods($twig::class));exit;
        try {
            $twig->resolveTemplate($templatePath);
        } catch (\Twig\Error\LoaderError $e) {
            return new JsonResponse([
                "msg" => "Quotation template '$template' not found",
                "error" => $e->getMessage(),
            ], 404);
        }

        // Internals
        // TIPS : below will need to be in custom config file,
        // by using K_TCPDF_EXTERNAL_CONFIG for example ?
        // apps/mws-sf-pdf-billings/backend/vendor/tecnickcom/tcpdf/config/tcpdf_config.php
        // define('K_TCPDF_CALLS_IN_HTML', true);

        // 🇺🇸🇺🇸 SEO
        Locale::setDefault('fr');
        $pdf->SetAuthor('Miguel Monwoo (service@monwoo.com)');
        $pdf->SetTitle('Devis 000');
        $pdf->SetSubject('Devis Monwoo');
        $pdf->SetKeywords('Monwoo, PWA, Svelte, PHP, Symfony');

        // 🇺🇸🇺🇸 Global page container
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // https://stackoverflow.com/questions/5503969/tcpdf-how-to-adjust-height-of-header
        $pdf->SetMargins(PDF_MARGIN_LEFT - 5, PDF_MARGIN_TOP - 10, PDF_MARGIN_RIGHT - 5);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 10);

        $pdf->setFontSubsetting(true);
        // $pdf->SetFont('dejavusans', '', 14, '', true);
        // $pdf->SetFont('roboto', '', 14, '', true);
        // set text shadow effect
        $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 0.7, 'blend_mode' => 'Normal'));

        // https://github.com/tecnickcom/TCPDF/blob/main/examples/example_009.php
        // $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // // set JPEG quality
        // $pdf->setJPEGQuality(75);
        // $pdf->Image('images/image_demo.jpg', 15, 140, 75, 113, 'JPG', 'http://www.tcpdf.org', '', true, 150, '', false, false, 1, false, false, false);

        // 🇺🇸🇺🇸 Header arrangements
        $PDF_HEADER_LOGO = null; // "logo.png";//any image file. check correct path.
        $PDF_HEADER_LOGO_WIDTH = 0; // "20";
        $PDF_HEADER_TITLE = null;
        // https://stackoverflow.com/questions/25934841/tcpdf-how-to-set-top-margin-in-header
        // $margin = $pdf->getMargins();
        // $pdf->SetY($margin['top']);
        $pdf->setHeaderMargin(3);
		$pdf->setHeaderFont(['freesans', '', 10]);

        // 🇺🇸🇺🇸 Header content
        $PDF_HEADER_STRING = "monwoo.com ($packageName v-$packageVersion)"
            . "                                                       "
            . "                                   Devis n° " . $bConfig->getQuotationNumber();
        // $PDF_HEADER_STRING = "Tel 1234567896 Fax 987654321\n"
        // . "E abc@gmail.com\n"
        // . "www.abc.com";
        // $PDF_HEADER_STRING = "";// "Devis n°" . $bConfig->getQuotationNumber();
        $pdf->SetHeaderData(
            $PDF_HEADER_LOGO,
            $PDF_HEADER_LOGO_WIDTH,
            $PDF_HEADER_TITLE,
            $PDF_HEADER_STRING,
            [0, 0, 0],
            [255, 255, 255], //[242, 242, 242]
        );

        // 🇺🇸🇺🇸 Footer arrangements
        // $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->setFooterFont(['freesans', '', 10]);

        // 🇺🇸🇺🇸 Footer content
        $pdf->setFooterContent('IBAN : DE72 1001 1001 2623 6346 33    -     BIC : NTSBDEB1XXX');

        // 🇺🇸🇺🇸 BODY arrangements
        $pdf->AddPage();
        // Set some content to print
        $html = $twig->render($templatePath, [
            'billingConfig' => $bConfig,
            'packageVersion' => $packageVersion, 'packageName' => $packageName,
            'pdfCssStyles' => file_get_contents($projectDir . '/public/pdf-views/theme.css'),
        ]);

        // https://stackoverflow.com/questions/14495688/how-to-put-html-data-into-header-of-tcpdf
        // $pdf->writeHTMLCell( // NOP, DO not go OVER header barre...
        //     $w = 0, $h = 0, $x = -1, $y = '',
        //     $leftHeader, $border = 0, $ln = 1, $fill = 0,
        //     $reseth = true, $align = 'top', $autopadding = true);
        $pdf->setTextShadow(array('enabled' => false));

        // Print text using writeHTMLCell()
        // $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->writeHTML($html, true, false, true, false, '');
        ob_end_clean(); // TODO : where is it mendatory ? done to avoid error header data already set

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
