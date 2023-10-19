<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Form\MwsOfferImportType;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Form\MwsUserFilterType;
use MWS\MoonManagerBundle\Repository\MwsOfferRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale<%app.supported_locales%>}/mws-offer',
    options: ['expose' => true],
)]
class MwsOfferController extends AbstractController
{
    public function __construct(
        protected LoggerInterface $logger,
        protected SerializerInterface $serializer,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $em
    ){
    }
    
    #[Route('/', name: 'mws_offer')]
    public function index(): Response
    {
        // TODO : depending of user roles : will have different preview systems
        return $this->redirectToRoute(
            'mws_offer_list',
            array_merge($request->query->all(), [
                // "filterTags" => $filterTags,
                // "keyword" => $keyword
            ]),
            Response::HTTP_SEE_OTHER
        );
    }
    
    #[Route('/lookup/{viewTemplate?}', name: 'mws_offer_lookup')]
    public function lookup(
        $viewTemplate,
        MwsOfferRepository $mwsOfferRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('mws_user_login');
        }

        $keyword = $request->query->get('keyword', null);
        $sourceRootLookupUrl = $request->query->get('sourceRootLookupUrl', null);
        

        $qb = $mwsOfferRepository->createQueryBuilder('u');

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                "searchKeyword" => $keyword,
                "sourceRootLookupUrl" => $sourceRootLookupUrl,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/mws_user/survey-js-models/MwsOfferLookupType.json.twig"
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $filterForm = $this->createForm(MwsSurveyJsType::class, $lastSearch);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted()) {
            $this->logger->debug("Did submit search form");

            if ($filterForm->isValid()) {
                $this->logger->debug("Search form ok");
                // dd($filterForm);

                $surveyAnswers = json_decode(
                    urldecode($filterForm->get('jsonResult')->getData()),
                    true
                );
                $keyword = $surveyAnswers['searchKeyword'] ?? null;
                $sourceRootLookupUrl = $surveyAnswers['sourceRootLookupUrl'] ?? null;
                return $this->redirectToRoute(
                    'mws_offer_lookup',
                    array_merge($request->query->all(), [
                        "viewTemplate" => $viewTemplate,
                        "keyword" => $keyword,
                        "sourceRootLookupUrl" => $sourceRootLookupUrl,
                    ]),
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        if ($keyword) {
            $qb
            ->andWhere("
                LOWER(REPLACE(u.clientUsername, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
                OR LOWER(REPLACE(u.contact1, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
                OR LOWER(REPLACE(u.contact2, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
                OR LOWER(REPLACE(u.contact3, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
            ")
            ->setParameter('keyword', '%' . $keyword . '%');
        }

        $query = $qb->getQuery();
        // dd($query->getResult());    
        $offers = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        $this->logger->debug("Succeed to list offers");

        return $this->render('@MoonManager/mws_offer/lookup.html.twig', [
            'offers' => $offers,
            'lookupForm' => $filterForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/fetch-root-url', name: 'mws_offer_fetchRootUrl')]
    public function fetchRootUrl(
        Request $request,
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('mws_user_login');
        }

        $url = $request->query->get('url', null);
        $this->logger->debug("Will fetch url : $url");

        // Or use : https://symfony.com/doc/current/http_client.html
        $respData = file_get_contents($url);
        $response = new Response($respData);

        // TIPS : too late after pdf echo...
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-chache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    #[Route('/import/{viewTemplate}/{format}',
        name: 'mws_offer_import',
        methods: ['GET','POST'],
        defaults: [
            'viewTemplate' => null,
            'format' => 'monwoo-extractor-export',
        ],
    )]
    public function import(
        string|null $viewTemplate,
        string $format,
        Request $request,
        MwsOfferRepository $mwsOfferRepository,
        SluggerInterface $slugger,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('mws_user_login');
        }

        $maxTime = 60 * 30; // 30 minutes max
        set_time_limit($maxTime);
        ini_set('max_execution_time', $maxTime);

        $forceRewrite = $request->query->get('forceRewrite', false);

        $uploadData = null;
        $form = $this->createForm(MwsOfferImportType::class, $uploadData);
        $form->handleRequest($request);
        $reportSummary = "";

        $this->logger->debug("Succeed to handle Request");
        if ($form->isSubmitted()) {
            $this->logger->debug("Succeed to submit form");

            if ($form->isValid()) {
                $this->logger->debug("Form is valid : ok");
                // https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpFoundation/File/UploadedFile.php
                // https://stackoverflow.com/questions/14462390/how-to-declare-the-type-for-local-variables-using-phpdoc-notation
                /** @var UploadedFile $importedUpload */
                $importedUpload = $form->get('importedUpload')->getData();
                if ($importedUpload) {
                    $originalFilename = $importedUpload->getClientOriginalName();
                    // TIPS : $importedUpload->guessExtension() is based on mime type and may fail
                    // to be .yaml if text detected... (will be .txt instead of .yaml...)
                    // $extension = $importedUpload->guessExtension();
                    $extension = array_slice(explode(".", $originalFilename), -1)[0];
                    $originalName = implode(".", array_slice(explode(".", $originalFilename), 0, -1));
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalName);

                    $newFilename = $safeFilename.'_'.uniqid().'.'.$extension;

                    $importContent = file_get_contents($importedUpload->getPathname());
                    // https://www.php.net/manual/fr/function.iconv.php
                    // https://www.php.net/manual/en/function.mb-detect-encoding.php
                    // $importContent = iconv("ISO-8859-1", "UTF-8", $importContent);
                    $importContentEncoding = mb_detect_encoding($importContent);
                    // dd($importContentEncoding);
                    $importContent = iconv($importContentEncoding,"UTF-8", $importContent);
                    // dd($importContent);
                    // TIPS : clean as soon as we can...
                    unlink($importedUpload->getPathname());
                    // $reportSummary = $importContent;
                    /** @var MwsOffer[] */
                    $offersDeserialized = $this->deserializeOffers($importContent, $format);
                    // dd($offersDeserialized);

                    $savedCount = 0;
                    /** @var MwsOffer $offer */
                    foreach ($offersDeserialized as $idx => $offer) {
                        $email = $offer->getEmail();
                        $phone = $offer->getPhone();

                        // TODO : add Tracking logs

                        // TIPS : case insensitive search instead of findBy
                        // TODO : add as repository method ?
                        $qb = $mwsOfferRepository
                        ->createQueryBuilder('o')
                        // ->where('upper(p.username) = upper(:username)')
                        ->where('o.username = :username')
                        ->setParameter('username', trim(ucfirst(strtolower($username))));
                        if ($email && strlen($email)) {
                            $qb->andWhere('p.email = :email')
                            ->setParameter('email', trim(strtolower($email)));
                        }
                        if ($phone && strlen($phone)) {
                            $qb->andWhere('p.phone = :phone')
                            ->setParameter('phone', trim(strtolower($phone)));
                        }
                        $query = $qb->getQuery();

                        // dd($query->getDQL());
                        // dump($query);
                        $allDuplicates = $query->execute();
                        
                        // dd($allDuplicates);
                        // var_dump($allDuplicates);exit;
                        if ($allDuplicates && count($allDuplicates)) {
                            if ($forceRewrite) {
                                $reportSummary .= "<strong>Surcharge le doublon : </strong> [$source ?, $username, $email, $phone]\n";
                                $inputOffer = $offer;
                                // $offer = $allDuplicates[0];
                                $offer = array_shift($allDuplicates);
                                $sync = function($path) use ($inputOffer, $offer) {
                                    $set = 'set' . ucfirst($path);
                                    $get = 'get' . ucfirst($path);
                                    $v =  $inputOffer->$get();
                                    if (null !== $v &&
                                    ((!is_string($v)) || strlen($v))) {
                                        $offer->$set($v);
                                    }
                                };
                                // TODO : factorize code with serializer service ? factorize to same location...
                                $sync('username');
                                $sync('leadStart');
                                $sync('adress');
                                $sync('postalCode');
                                $sync('phone');
                                $sync('email');
                                $sync('comment');
                                $sync('currentStatus');
                                // dump($inputOffer);
                                // dd($offer);
                                // CLEAN all possible other duplicates :
                                foreach ($allDuplicates as $otherDups) {
                                    $em->remove($otherDups);
                                }
                            } else {
                                $reportSummary .= "<strong>Ignore le doublon : </strong> [$lastName,  $firstName, $email, $phone]\n";
                                continue;    
                            }    
                        }

                        // TODO : add comment to some traking entities, column 'Observations...' or too huge for nothing ?

                        // if (!$offer->getSourceFile()
                        // || !strlen($offer->getSourceFile())) {
                        //     $offer->setSourceFile('unknown');
                        // }

                        // Default auto compute values :
                        if (!$offer->getCurrentStatus()) {
                            // TODO : from .env config file ? or from DB ? (status with tags for default selections ?)
                            $offer->setCurrentStatus('a-relancer');
                            // dd($offer);    
                        }

                        $em->persist($offer);
                        $em->flush();
                        $savedCount++;
                    }
                    $reportSummary .= "\n\nEnregistrement de $savedCount offres OK \n";

                    // var_dump($extension);var_dump($importContent);var_dump($offersDeserialized); exit;
                }
            }
        }
        $formatToText = [
            // 'tsv' => "Tab-separated values (TSV)",
            'csv' => "Comma-separated values (CSV)",
            'json' => "JavaScript Object Notation (JSON)",            
        ];
        return $this->render('@MoonManager/mws_offer/import.html.twig', [
            'reportSummary' => $reportSummary,
            'format' => $format,
            'uploadForm' => $form,
            'viewTemplate' => $viewTemplate,
            'title' => 'Importer les offres via ' . ($formatToText[$format] ?? $format)
        ]);
    }

    // TODO : inside some Services helper class instead ?
    // TODO : more like 'loadOffers' than deserialize,
    //        will save in db too for code factorisation purpose...
    public function deserializeOffers($data, $format, $sourceId = null) {
        /** @param MwsOffer[] **/
        $out = null;
        // TODO : add custom serializer format instead of if switch ?
        if ($format === 'monwoo-extractor-export') {
            $data = json_decode($data, true);

            $sources = array_keys($data);
            foreach ($data as $sourceSlug => $board) {
                // foreach ($board['users'] as $contactSlug => $c) {
                //     # TODO : add contacts (how to send back ? inside _contact props ?)
                // }
                $contactIndex = $board['users'];
                foreach ($board['projects'] as $offerSlug => $o) {
                    $offer = new MwsOffer();

                    $userId = $o["uId"] ?? null;
                    $offer->setClientUsername($userId);
                    // $offer->setTitle($o['title']);

                    // TODO : getClientSlug that ensure contact unicity ?

                    $c = $contactIndex[$userId] ?? [];
                    // TODO : contactSlug = $c .... ; => unicity check in db before creating new one...
                    $contact = new MwsContact();
                    $contact->setSourceId(
                        $sourceId
                    );
                    $contact->setSourceDetail(
                        json_encode($c)
                    );

                    $offer->setClientContact($contact[]);
                }
            }
        } else {
            $out = $this->serializer->deserialize(
                $data,
                MwsOffer::class . "[]",
                $format,
                // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            );
        }
        return $out;
    }

    /** @param MwsOffer[] $offers */
    public function serializeOffers($offers, $format) {
        $out = null;
        // TODO : add custom serializer format instead of if switch ?
        if ($format === 'monwoo-extractor-export') {

        } else {
            $out = $this->serializer->serialize(
                $offers,
                MwsOffer::class . "[]",
                $format,
                // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            );    
        }
        return $out;
    }
}
