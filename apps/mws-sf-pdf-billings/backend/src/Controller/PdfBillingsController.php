<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Controller;

use App\Entity\BillingConfig;
use App\Entity\Outlay;
use App\Entity\Transaction;
use App\Form\BillingConfigSubmitableType;
use App\Repository\BillingConfigRepository;
use App\Repository\TransactionRepository;
use App\Services\MwsTCPDF;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Asset\Packages;

use Qipsius\TCPDFBundle\Controller\TCPDFController;
use Doctrine\ORM\EntityManagerInterface;
use Locale;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Font\NotoSans;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

# https://symfony.com/doc/6.2/the-fast-track/en/28-intl.html
# https://symfony.com/doc/5.4/the-fast-track/en/28-intl.html
// TODO : from config ? also setup in apps/mws-sf-pdf-billings/backend/config/packages/translation.yaml

// #[Route(
//     '/{_locale?<%app.supported_locales%>}/',
//     defaults: [
//         '_locale' => null,
//     ]
// )] => sounds gread, but do not redirect '/' and add wierdy behavior on param checks...


#[Route('/{_locale<%app.supported_locales%>}/')]
class PdfBillingsController extends AbstractController
{
    protected TCPDFController $tcpdf;
    protected $billingConfigFactory;
    protected $em;
    protected $logger;
    protected Serializer $serializer;

    public function __construct(
        LoggerInterface $logger,
        TCPDFController $tcpdf,
        EntityManagerInterface $em
    ) {
        // ob_start();

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
            // $bConfig->setQuotationSourceNumber('________________');
            $bConfig->setQuotationNumber('________________');
            // $bConfig->setClientEmail('📂@ ______________________________');
            // $bConfig->setClientTel('📞 ______________________________'); NOP, not having loaded font for it, // TODO : load emoticon fonts ?
            $bConfig->setClientEmail('______________________________');
            $bConfig->setClientTel('______________________________');
            $bConfig->setClientSIRET('______________________________');
            $bConfig->setClientTvaIntracom('______________________________');
            // $bConfig->setClientAddressL1('🧭 ______________________________');
            $bConfig->setClientAddressL1('______________________________');
            $bConfig->setClientAddressL2('______________________________');
            $bConfig->setClientWebsite('______________________________');
            $bConfig->setClientLogoUrl('______________________________');
            $bConfig->setClientName('______________________________');
            $bConfig->setClientName('______________________________');

            // TIPS : COULD be setup, but will not be dynamic on current day at view side if so :
            // $timable = new DateTime();
            // $bConfig->setQuotationStartDay($timable);
            // // $timable->add(new DateInterval("PT24H"));
            // $timable->add(new DateInterval("P15D"));
            // $bConfig->setQuotationEndDay($timable);

            $em->persist($bConfig);
            $em->flush();
            // ob_end_clean();

            return $bConfig;
        };

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        // $normalizers = [new ObjectNormalizer()];
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, string $format, array $context): string {
                // return "**" . (string)$object . "**";
                return "****";
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $normalizers = [ $normalizer ];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    protected function setupBillingConfigDefaults(BillingConfig &$bConfig) {
        $template = $bConfig->getQuotationTemplate() ?? 'monwoo';
        // TIPS : hard coded outlays default if no outlays is already set in database :
        // TODO : add test on first add/remove to show default only if no changes occures ?
        if ($bConfig->getOutlays()->count() === 0 && !$bConfig->isHideDefaultOutlaysOnEmptyOutlays()) {
            $twig = $this->container->get('twig');
            if ('monwoo' === $template
                || 'monwoo-02-wp-e-com' === $template
                || 'monwoo-03-php-backend' === $template
                || 'monwoo-05-php-crm' === $template
            ) {
                $defaultOutlay = new Outlay();
                $defaultOutlay->setProviderName("lws.fr");
                $defaultOutlay->setProviderShortDescription("(Payable hors Monwoo)<br/>Hébergment LWS");
                // TIPS : for added price to count in total business offer
                // $defaultOutlay->setProviderAddedPrice(130);
                $defaultOutlay->setProviderTotalWithTaxesForseenForClient(95);
                // $defaultOutlay->setProviderAddedPriceTaxes(130 * (1 - 1/1.2)); // 20% de taxes
                $defaultOutlay->setProviderAddedPriceTaxes(null); // 20% de taxes
                $defaultOutlay->setProviderAddedPriceTaxesPercent(0.2); // 20% de taxes
                $defaultOutlay->setinsertPageBreakAfter(true);

                // TIPS : do template after outlay setup to use it in template...
                $defaultOutlay->setProviderDetails(
                    $twig->render('pdf-billings/pdf-views/quotation-outlay-details-lws.html.twig', [
                        "outlay" => $defaultOutlay
                    ])
                );        
                $bConfig->addOutlay($defaultOutlay);

                $defaultOutlay = new Outlay();
                $defaultOutlay->setProviderName("codeur.com");
                $defaultOutlay->setProviderShortDescription("(contractuel)<br/>Suivi de mission");
                $defaultOutlay->setPercentOnBusinessTotal(0.04);
                $defaultOutlay->settaxesPercentAddedToPercentOnBusinessTotal(0.2); // 20% de taxes à rajouter sur comission codeur.com
                $defaultOutlay->setProviderAddedPriceTaxes(null); // With percent, will precead other value

                // TIPS : do template after outlay setup to use it in template...
                $defaultOutlay->setProviderDetails(
                    $twig->render('pdf-billings/pdf-views/quotation-outlay-details-codeur-com.html.twig', [
                        "outlay" => $defaultOutlay
                    ])
                );
                $bConfig->addOutlay($defaultOutlay);
            }
            // We DO NOT persiste $defaultOutlay since we let end user to chose to save with it or not...
            // TODO : doc : if user remove all outlets, defaults outlets will comme back if
            // hideDefaultOutlaysOnEmptyOutlays from BillingConfig is set to true...

            if ('monwoo-04-hybrid-app' === $template) {
                $defaultOutlay = new Outlay();
                $defaultOutlay->setProviderName("codeur.com"); 
                $defaultOutlay->setProviderShortDescription("(contractuel)<br/>Suivi de mission");
                $defaultOutlay->setPercentOnBusinessTotal(0.04);
                // TODO : SOUND OK if using setTaxesPercentIncludedInPercentOnBusinessTotal OR
                // settaxesPercentAddedToPercentOnBusinessTotal
                // BUT using BOTH of them have some wrong TVA Calculation or ok since
                // tva on Business should include tva in business to compute or not ? 
                // OK for now if using ONE OR THE other...
                // $defaultOutlay->setTaxesPercentIncludedInPercentOnBusinessTotal(0.2); // 20% de taxes inclus dans comission codeur.com
                $defaultOutlay->settaxesPercentAddedToPercentOnBusinessTotal(0.2); // 20% de taxes à rajouter sur comission codeur.com
                $defaultOutlay->setProviderAddedPriceTaxes(null); // With percent, will precead other value
                $defaultOutlay->setProviderDetails(
                    $twig->render('pdf-billings/pdf-views/quotation-outlay-details-codeur-com.html.twig', [
                        "outlay" => $defaultOutlay
                    ])
                );
                $bConfig->addOutlay($defaultOutlay);
            }
        }

        // // TIPS : bellow for quick added transaction check on each page refresh :
        // $defaultTransaction = new Transaction();
        // $defaultTransaction->setPaymentMethod("Test pay way");
        // $defaultTransaction->setReceptionNumber("20230725-R-M00");
        // // https://stackoverflow.com/questions/470617/how-do-i-get-the-current-date-and-time-in-php
        // // $now = new DateTime(null, new DateTimeZone('America/New_York'));
        // // $now->setTimezone(new DateTimeZone('Europe/London'));    // Another way
        // // echo $now->getTimezone();
        // $defaultTransaction->setReceptionDate(new DateTime());
        // $defaultTransaction->setLabel("Reçu");
        // $defaultTransaction->setPriceWithoutTaxes(42);

        // $bConfig->addTransaction($defaultTransaction);
        // // Transaction MUST be SAVED to be visible since fetched from db query on ID...
        // $this->em->persist($bConfig);
        // $this->em->flush();
    }

    protected function getDefaultTemplateData(string $template) {
        switch ($template) {
            case 'monwoo-02-wp-e-com':
                return [
                    "defaultBusinessWorkloadHours" => 4.5,
                    "pricePerHourWithoutDiscount" => 80,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-wa-config-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;
            case 'monwoo-03-php-backend':
                return [
                    "defaultBusinessWorkloadHours" => 15,
                    "pricePerHourWithoutDiscount" => 100,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-php-backend-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;
            case 'monwoo-04-hybrid-app':
                return [
                    "defaultBusinessWorkloadHours" => 18,
                    "pricePerHourWithoutDiscount" => 120,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-hybrid-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;        
            case 'monwoo-05-php-crm':
                return [
                    "defaultBusinessWorkloadHours" => 18,
                    "pricePerHourWithoutDiscount" => 80,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-php-crm-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;    
            case 'monwoo-06-analytical-study':
                return [
                    "defaultBusinessWorkloadHours" => 2,
                    "pricePerHourWithoutDiscount" => 60,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-analytical-study-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;
            case 'monwoo-07-upkeep':
                return [
                    "defaultBusinessWorkloadHours" => 3,
                    "pricePerHourWithoutDiscount" => 60,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-upkeep-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;
            case 'monwoo-08-backend-learning':
                return [
                    "defaultBusinessWorkloadHours" => 15,
                    "pricePerHourWithoutDiscount" => 100,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-backend-learning-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;
            case 'monwoo-09-empty':
                return [
                    "defaultBusinessWorkloadHours" => 0,
                    "pricePerHourWithoutDiscount" => 0,
                    "businessWorkloadTemplate" => null,//"pdf-billings/pdf-views/business-item-backend-learning-details.html.twig",
                    "licenseWpDisplayPrice" => 0,
                    "licenseWpDisplayDiscount" => 0,
                ];
                break;    
            default:
                return [
                    "defaultBusinessWorkloadHours" => 6,
                    "pricePerHourWithoutDiscount" => 60,
                    "businessWorkloadTemplate" => "pdf-billings/pdf-views/business-item-svelte-workload-details.html.twig",
                    "licenseWpDisplayPrice" => 40,
                    "licenseWpDisplayDiscount" => 0.25,
                ];
                break;
        }
    }

    protected function getQrCodeStamp($data, $logoPath, $label = '© Monwoo') {
        try {
            $this->logger->debug("getQrCodeStamp: " . $data);
            // https://stackoverflow.com/questions/10991035/best-way-to-compress-string-in-php
            // TIPS : not a good idea, will explode qr code limits for small readers
            // $compressed = gzdeflate($data,  9);
            // $compressed = gzdeflate($compressed, 9);
            // // b64 to bring back string data
            // // $compressed = base64_encode($compressed);
            // $compressed = urlencode($compressed);
            $hash = md5($data);


            // $this->logger->debug("getQrCodeStamp: compressed :" . $compressed);
            $this->logger->debug("getQrCodeStamp: hash :" . $hash);

            // echo strlen($compressed); //99 bytes
            // echo gzinflate(gzinflate($compressed));

            // TIPS to QUICKY validate compressed : scan it, copy the data and launch :
            // php -a
            // $data = 'AbEBTv6VU01vozAQ/S8+RkXabm+5NU1WRYoatLDKoVqtDB6IFTPD2uPuRlX/ew2BRKJfhAvSvA8/84ZnURgNyKnxlZiLKBJX/eRB1hAmfz59AvuvJ5asCR98nYN9R3KyXNVSmymeR3oGF5DT+Ocqu8D7ScbIVhZUTxfdKmXBufX15ZLv0yVbyJ1mmC5YU0W/7KRvlXunsc0TJGKO3pjzbEt2b0iqe/LWfQQugUOHJ/hUfsrS8lIe3gArVO+NM6gbI7tb1oT/iKJvN1Gza6JcFntAFbI2YItwu6V2BXnkwYI8G3kICR6fhVZifh2Ylp60Atuv7HqbnuUbXPRXyMLRZnBh+R9ccqTEWBivQMWYfKoZjumGW827rDX5QdYBYHjddW2M2WEDQCVWF/Ax0hl9AffZBpZ3kLwhhhBDcjEvpXFwtkt3FBoCV1jdtA0MRhodWE5kBQsLcr+Akiyc1CP0tuT2Fx9Zj5Yi18ZorO4IS121RYnZLIpmM/H7StTSVhozagbycbAg5vZPbGcvgdYXuwvuSyilN7w51r7BVd3wYTMsQZ+kd+myd6sYM9SjIzowrOMZenkF'
            // echo gzinflate(gzinflate(base64_decode($data)));
            // echo gzinflate(gzinflate(urldecode($data)));
            // You should see the json data use to setup the billings...
            // But in the end, even with compression, it's too huge for qr code
            // so using hash instead (for sample purpose, need secu backend sync ?) :

            // https://php-download.com/package/endroid/qr-code/example
            // https://github.com/endroid/qr-code
            $writer = new PngWriter();
            // $qrCode = QrCode::create("http://certif.localhost/?data=$compressed")
            $qrCode = QrCode::create("http://certif.localhost/?dataMD5=$hash")
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelMedium())
                // ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                // ->setSize(480)
                ->setSize(120)
                ->setMargin(0)
                // ->setPadding(0)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                // ->setLogoPunchoutBackground(true)
                // ->logoPunchoutBackground(true) // with builder....
                // ->validateResult(false)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            // var_dump($logoPath); exit;
            // throw new \Exception("force wrong");
            // Qr code will try to fetch headers if urls look 
            // like web url for local file due to tcpdf formats...
            $logoPath = str_replace("file://", "", $logoPath);
            $logo = $logoPath ? Logo::create($logoPath)
                ->setResizeToWidth(21)->setPunchoutBackground(true) : null;
            // $qrLabel = Label::create('')->setFont(new NotoSans(8));
            return $writer->write(
                $qrCode,
                $logo,
                null // $qrLabel->setText($label)->setFont(new NotoSans(12))
            )->getDataUri();
        } catch (\Exception $e) {
            $this->logger->error("Fail to generate QRCODE : " . $e, ['err' => $e]);
            return null;
        }

        // https://www.binaryboxtuts.com/php-tutorials/symfony-tutorials/symfony-5-qr-code-generator-tutorial/
        // https://github.com/endroid/qr-code-bundle
        // $qrCodes = [];
        // $qrCodes['img'] = $writer->write($qrCode, $logo)->getDataUri();
        // $qrCodes['simple'] = $writer->write(
        //                         $qrCode,
        //                         null,
        //                         $label->setText('Simple')
        //                     )->getDataUri();
 
        // $qrCode->setForegroundColor(new Color(255, 0, 0));
        // $qrCodes['changeColor'] = $writer->write(
        //     $qrCode,
        //     null,
        //     $label->setText('Color Change')
        // )->getDataUri();
 
        // $qrCode->setForegroundColor(new Color(0, 0, 0))->setBackgroundColor(new Color(255, 0, 0));
        // $qrCodes['changeBgColor'] = $writer->write(
        //     $qrCode,
        //     null,
        //     $label->setText('Background Color Change')
        // )->getDataUri();
 
        // $qrCode->setSize(200)->setForegroundColor(new Color(0, 0, 0))->setBackgroundColor(new Color(255, 255, 255));
        // $qrCodes['withImage'] = $writer->write(
        //     $qrCode,
        //     $logo,
        //     $label->setText('With Image')->setFont(new NotoSans(20))
        // )->getDataUri();
    }

    #[Route('', name: 'app_pdf_billings')]
    public function index(
        Request $request,
        BillingConfigRepository $bConfigRepository
    ): Response {
        // $clientId = $request->get('clientId');

        // https://stackoverflow.com/questions/21124450/how-to-use-curl-multipart-form-data-to-post-array-field-from-command-line

        $rawBillingConfigFromGET = $request->query->get('billing_config_submitable'); // This way : will LOAD in GET, set in POST request if not in POST mode ;)
        $rawBillingConfigFromPOST = $request->request->get('billing_config_submitable'); // To read from POST ONLY
        $rawBillingConfig = $rawBillingConfigFromPOST ?? $rawBillingConfigFromGET;
        $clientSlug = $rawBillingConfig
            ? ($rawBillingConfig['clientSlug'] ?? null) : null;
        $clientSlug = $clientSlug ? $clientSlug : '--';
        // var_dump($clientSlug); exit;

        $bConfig = $bConfigRepository->findOneBy([
            'clientSlug' => $clientSlug, // Default empty client, all fillable by hand version...
        ]) ?? ($this->billingConfigFactory)($clientSlug);

        // var_dump($rawBillingConfigFromGET); exit;
        // TIPS : MAKE GET param ovewrite post value for some key config keys :
        $templateFromGET = $rawBillingConfigFromGET['quotationTemplate'] ?? null;
        if ($templateFromGET) {
            $bConfig->setQuotationTemplate($templateFromGET);
            $this->em->persist($bConfig);
            $this->em->flush(); // For next conccurency possible issue, save and flush new template choice
        }
        // Now you can use links like :
        // http://localhost:8000/?billing_config_submitable%5BclientSlug%5D=--&billing_config_submitable%5BquotationTemplate%5D=monwoo-06-analytical-study


        // var_dump($bConfig);exit;
        // $this->logger->info('From route [app_pdf_billings] :' . json_encode(get_object_vars($bConfig), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        // $this->logger->info('From route [app_pdf_billings] :' . json_encode($bConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $this->logger->debug('From route [app_pdf_billings] :' . $this->serializer->serialize(
            $bConfig, 'json'
        ));        

        // https://symfony.com/doc/current/form/form_collections.html#allowing-tags-to-be-removed
        $originalOutlays = new ArrayCollection();
        $originalBConfigsByOutlay = [];
        // Create an ArrayCollection of the current Outlay objects in the database
        foreach ($bConfig->getOutlays() as $outlay) {
            $originalOutlays->add($outlay);
            $originalBConfigsByOutlay["".$outlay] = [];
            foreach ($outlay->getBillingConfigs() as $originalOutlaysBConfig) {
                $originalBConfigsByOutlay["".$outlay][] = $originalOutlaysBConfig;
            }
        }

        $this->setupBillingConfigDefaults($bConfig);

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
                // https://symfony.com/doc/current/form/form_collections.html#allowing-tags-to-be-removed
                
                // remove the relationship between the outlay and the bConfig
                foreach ($originalOutlays as $orignialOutlay) {
                    if (false === $bConfig->getOutlays()->contains($orignialOutlay)) {
                        // remove the Task from the Outlay
                        // $orignialOutlay->getBillingConfigs()->removeElement($bConfig);
                        $orignialOutlay->removeBillingConfig($bConfig);

                        // if it was a many-to-one relationship, remove the relationship like this
                        // $orignialOutlay->setTask(null);

                        $this->em->persist($orignialOutlay);
                        // if you wanted to delete the Outlay entirely, you can also do that
                        // $entityManager->remove($orignialOutlay);
                    }
                }

                // Handle Outlay embed form linking back to BillingConfigs that can be selected/unSelected
                // var_dump("Testing outlays from " . $bConfig . '.');
                foreach ($bConfig->getOutlays() as $outlay) {
                    // ensure multiple choice-add is OK
                    foreach ($outlay->getBillingConfigs() as $outlayBConfig) {
                        if (false === $outlayBConfig->getOutlays()->contains($outlay)) {
                            $outlayBConfig->addOutlay($outlay);
                            $this->em->persist($outlayBConfig);
                        }
                    }
                    // ensure multiple choice-remove is OK
                    // var_dump("Testing delete from original for " . $outlay . '.');
                    if ($originalOutlay = $originalOutlays->findFirst(function ($k, $e) use ($outlay) {
                        return $e === $outlay;
                    })) {
                        // var_dump("Testing original " . $originalOutlay . '.');
                        // TIPS : getBillingConfigs on original Outlay will not work :
                        // $originalOutlaysBConfigs = $originalOutlay->getBillingConfigs()
                        $originalOutlaysBConfigs = $originalBConfigsByOutlay["".$originalOutlay];
                        foreach ($originalOutlaysBConfigs as $originalOutlayBConfig) {
                            // var_dump("Testing " . $originalOutlayBConfig . ' from ' . $originalOutlay
                            // . " aginst " . $outlay);
                            if (false === $outlay->getBillingConfigs()->contains($originalOutlayBConfig)) {
                                // var_dump("Removing " . $outlay . ' from ' . $originalOutlayBConfig); exit;
                                // $originalOutlayBConfig->removeOutlay($outlay); // TODO : same as remove $originalOutlay ?
                                $originalOutlayBConfig->removeOutlay($originalOutlay);
                                $this->em->persist($originalOutlayBConfig);
                            }
                        }
                    }
                }
                // exit;
                $this->em->persist($bConfig);
                $this->em->flush();

                // Sounds like previous deletes do not reflect on saved form,
                // need to load from get to see changes...
                $this->em->refresh($bConfig);

                // // TIPS :
                // // Will error : You cannot change the data of a submitted form.
                // $form->setData($bConfig);
                // // So right way sound more like :
                // $builder->addEventListener(FormEvents::POST_SUBMIT, 
                //     function (FormEvent $event) { 
                //     if(!$event->getForm()->isValid()){
                //         $event->getForm()->get('field1')->setData('value1'); 
                //     }
                // });
    
                // TIPS : un-comment below if you want to redirect to full PDF view at form submit
                // return $this->redirectToRoute('app_pdf_billings_view', [], Response::HTTP_SEE_OTHER);

                // TIPS : redirect to self view with GET client ID to force data db refresh
                //       after embedded form edits not reflected in validated form...
                return $this->redirectToRoute('app_pdf_billings', [
                    "billing_config_submitable" => [
                        "clientSlug" => $clientSlug
                    ]
                ]);
            } else {
                // var_dump($form->getErrors(true)->__toString());exit;
                $this->logger->error(
                    'Form submit errors : '
                        . $form->getErrors(true)->__toString()
                );
            }
        }
        // ob_end_clean();
        // TODO : add configs for default template 'monwoo' if not already set up ?
        $templateData = $this->getDefaultTemplateData($bConfig->getQuotationTemplate() ?? 'monwoo');

        // return $this->render('pdf-billings/index.html.twig', [
        //     'form' => $form->createView(),
        return $this->renderForm('pdf-billings/index.html.twig', array_merge($templateData, [
            'businessWorkloadHours' => $bConfig->getBusinessWorkloadHours()
            ?? $templateData['defaultBusinessWorkloadHours'],
            'billingConfig' => $bConfig,
            'form' => $form,
            'title' => 'MWS SF PDF Billings - Index'
        ]));
    }

    // https://symfony.com/doc/current/routing.html
    // https://symfony.com/doc/5.4/routing.html#parameters-validation
    #[Route(
        '/view/{clientSlug}/{viewPart<CGV>?}',
        defaults: [
            'clientSlug' => 1,
            'viewPart' => ''
        ],
        name: 'app_pdf_billings_view'
    )]
    public function view(
        Request $request,
        string $clientSlug,
        string $viewPart,
        // EngineInterface $tplEngine,
        string $projectDir,
        BillingConfigRepository $bConfigRepository,
        TransactionRepository $transactionRepository,
        Packages $packages,
        // UrlGeneratorInterface $urlGenerator,
    ): Response {
        // Missing deps injections in new version ?
        // Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $tplEngine ?
        // $html = $tplEngine->render('pdf-billings/pdf-views/monwoo-quotation.html.twig', [
        ob_start();

        // TODO : going further with the timestamp refresh system : client view with same
        // timestamp might have refresh issues if some cache systems ignore headers
        // so main idea it to re-direct to new timestamp
        // if userlink timestamp is higher than current billingConfig lastModification timestamp...

        $twig = $this->container->get('twig');

        $bConfig = $bConfigRepository->findOneBy([
            'clientSlug' => $clientSlug, // Default empty client, all fillable by hand version...
        ]) ?? $bConfigRepository->findOneBy([
            'clientSlug' => '--', // Default empty client, all fillable by hand version...
        ]) ?? ($this->billingConfigFactory)();
        $labelByDocType = [
            "devis" => "Devis",
            "facture" => "Facture",
            "proforma" => "Proforma",
        ];
        $docTypeLabel = $labelByDocType[$bConfig->getDocumentType() ?? 'devis'];

        $template = $bConfig->getQuotationTemplate() ?? 'monwoo';
        // TODO : template data inside config ? make it some optional config form on template changes ?
        // $templateData = $bConfig->getTemplateData() ?? $this->defaultTemplateData($template);
        $templateData = $this->getDefaultTemplateData($template);

        $defaultQuotationNumber =
        $bConfig->getQuotationNumber() ?? $bConfig->getQuotationSourceNumber();
        $defaultQuotationSourceNumber =
        $bConfig->getQuotationSourceNumber() ?? $bConfig->getQuotationNumber(); 

        // TODO : from config or .env params ?
        $businessSignatureImg = 'file://' . $projectDir . '/var/businessSignature.png';
        // First try png extension (will work with tcpdf bg ? // TODO test...)
        // TIPS : png have more visual animation power, but .jpg also have better compressions...
        if (!file_exists($businessSignatureImg)) {
            $businessSignatureImg = 'file://' . $projectDir . '/var/businessSignature.jpg';
            if (!file_exists($businessSignatureImg)) {
                $businessSignatureImg = null;
            }
        }
        $this->setupBillingConfigDefaults($bConfig);
        // https://stackoverflow.com/questions/8811251/how-to-get-the-full-url-for-an-asset-in-controller
        $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

        $defaultLogoPublic = $baseurl . $packages->getUrl('/medias/LogoMonwooDemo.jpg');
        $defaultLogoPrivate = 'file://' . $projectDir . '/var/businessLogo.jpg';
        $businessLogo = $bConfig->getBusinessLogo() ? $bConfig->getBusinessLogo()
        // : $urlGenerator->generate('/public/medias/LogoMonwooDemo.jpg');
        : (file_exists($defaultLogoPrivate) ? $defaultLogoPrivate : $defaultLogoPublic);
        // Warning: get_headers(): This function may only be used against URLs with below :
        // : 'file://' . $projectDir . '/public/medias/LogoMonwooDemo.jpg';

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
        $pdf->SetTitle($businessSignatureImg
            ? "{$docTypeLabel} Monwoo n° " . $defaultQuotationNumber
            : "Démo {$docTypeLabel} Monwoo n° " . $defaultQuotationNumber
        );
        $pdf->SetSubject($businessSignatureImg
            ? "{$docTypeLabel} Monwoo"
            : "Démonstration de {$docTypeLabel} Monwoo"
        );
        $pdf->SetKeywords('Monwoo, PWA, Svelte, PHP, Symfony');

        // 🇺🇸🇺🇸 Signing
        // TODO : data backup and signing ? simple b64 chuncked encode
        // to start (data-01 ... data-n, max 255 char per keys...)
        // + serialize inputs data inside PDF for contrôle
        // + load devis feature FROM pdf to do Billings....
        // (In case client is not in DB anymore due ton WEB gdrp compilance...)

        // + add 'loaded from pdf' attribut in BillingConfig but not in form inputs...
        // Will be set to import date if config IS loaded from pdf...
        // + Add last edit date + first édit date...

        // Allow pdf meta to hold all inputs data (for reload / debug and certification purpose)
        $footprint = $this->serializer->serialize(
            $bConfig, 'json'
        );
        // Sound hard in meta data, new idea from past r&d : use QrCode ;)
        $footprintQrCodeUrl = $this->getQrCodeStamp($footprint, $businessLogo);
        // var_dump($footprintQrCodeUrl);exit;

        // 🇺🇸🇺🇸 Global page container
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // https://stackoverflow.com/questions/5503969/tcpdf-how-to-adjust-height-of-header
        $pdf->SetMargins(PDF_MARGIN_LEFT - 10, PDF_MARGIN_TOP - 12, PDF_MARGIN_RIGHT - 10);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 12);

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
        $docHeaderByDocType = [
            "devis" => "       Devis",
            "facture" => "     Facture",
            "proforma" => "Proforma",
        ];
        $PDF_HEADER_STRING = "monwoo.com ($packageName v-$packageVersion)"
        . (
            $businessSignatureImg
            ? "                                                                   "
            : "   Brouillon à confirmer                            "
        )
        // . "                                   Devis n° " . $defaultQuotationNumber;
        . "                           "
        . $docHeaderByDocType[$bConfig->getDocumentType() ?? 'devis']
        . " n° " . $defaultQuotationNumber;
        // $PDF_HEADER_STRING = "Tel 1234567896 Fax 987654321\n"
        // . "E abc@gmail.com\n"
        // . "www.abc.com";
        // $PDF_HEADER_STRING = "";// "Devis n°" . $defaultQuotationNumber;
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
        $html = $twig->render($templatePath, array_merge([
            'labelByDocType' => $labelByDocType,
            'docTypeLabel' => $docTypeLabel,
            'billingConfig' => $bConfig, 'businessSignatureImg' => $businessSignatureImg,
            'transactionsWithTotals' => $transactionRepository
            ->findByBConfigIdWithTotal($bConfig->getId()),
            'viewPart' => $viewPart, 'footprintQrCodeUrl' => $footprintQrCodeUrl,
            'businessLogo' => $businessLogo,
            'packageVersion' => $packageVersion, 'packageName' => $packageName,
            'pdfCssStyles' => file_get_contents($projectDir . '/public/pdf-views/theme.css'),
            'defaultQuotationNumber' => $defaultQuotationNumber,
            'defaultQuotationSourceNumber' => $defaultQuotationSourceNumber,
        ], $templateData));

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
        $response = new Response($pdf->Output($businessSignatureImg
            ? "{$docTypeLabel}Monwoo" . $defaultQuotationNumber . '.pdf'
            : "Demo{$docTypeLabel}Monwoo" . $defaultQuotationNumber . '.pdf'
        ));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-chache');
        $response->headers->set('Expires', '0');

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
