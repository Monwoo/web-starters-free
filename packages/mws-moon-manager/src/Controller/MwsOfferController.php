<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Entity\MwsContact;
use MWS\MoonManagerBundle\Entity\MwsContactTracking;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;
use MWS\MoonManagerBundle\Entity\MwsOfferTracking;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsOfferImportType;
use MWS\MoonManagerBundle\Form\MwsOfferStatusType;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Form\MwsUserFilterType;
use MWS\MoonManagerBundle\Repository\MwsOfferRepository;
use MWS\MoonManagerBundle\Repository\MwsOfferStatusRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale<%app.supported_locales%>}/mws-offer',
    options: ['expose' => true],
)]
class MwsOfferController extends AbstractController
{
    public function __construct(
        protected Security $security,
        protected LoggerInterface $logger,
        protected SerializerInterface $serializer,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $em,
        protected SluggerInterface $slugger,
    ){
    }
    
    #[Route('/', name: 'mws_offer')]
    public function index(): Response
    {
        // TODO : depending of user roles : will have different preview systems
        return $this->redirectToRoute(
            'mws_offer_list',
            array_merge($request->query->all(), [
                "page" => 1,
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
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $keyword = $request->query->get('keyword', null);
        $sourceRootLookupUrl = $request->query->get('sourceRootLookupUrl', null);
        

        $qb = $mwsOfferRepository->createQueryBuilder('o');

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                "searchKeyword" => $keyword,
                "sourceRootLookupUrl" => $sourceRootLookupUrl,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsOfferLookupType.json.twig"
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
                        "page" => 1,
                        "sourceRootLookupUrl" => $sourceRootLookupUrl,
                    ]),
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        if ($keyword) {
            $qb
            // LOWER(REPLACE(o.clientUsername, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
            // OR LOWER(REPLACE(o.contact1, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
            ->andWhere("
                LOWER(REPLACE(o.clientUsername, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE(o.contact1, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE(o.contact2, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE(o.contact3, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE(o.title, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE(o.description, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE(o.budget, ' ', '')) LIKE :keyword
            ")
            ->setParameter('keyword', '%' . strtolower(str_replace(" ", "", $keyword)) . '%');
        }

        $query = $qb->getQuery();
        // dd($query->getResult());    
        $offers = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $request->query->getInt('pageLimit', 10), /*page number*/
        );

        $this->logger->debug("Succeed to list offers");
        // dd($offers);
        $slugToOfferTag = $mwsOfferStatusRepository->findAll();
        $slugToOfferTag = array_combine(array_map(function($t) {
            return $t->getSlug();
        }, $slugToOfferTag), $slugToOfferTag);

        return $this->render('@MoonManager/mws_offer/lookup.html.twig', [
            'slugToOfferTag' => $slugToOfferTag,
            'offers' => $offers,
            'lookupForm' => $filterForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/view/{offerSlug}/{viewTemplate?}', name: 'mws_offer_view')]
    public function view(
        $offerSlug,
        $viewTemplate,
        MwsOfferRepository $mwsOfferRepository,
        Request $request,
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $offer = $mwsOfferRepository->findOneBy([
            'slug' => $offerSlug
        ]);

        return $this->render('@MoonManager/mws_offer/view.html.twig', [
            'offer' => $offer,
            'viewTemplate' => $viewTemplate,
        ]);
    }


    // Tags are status AND category of status (a category is also a status...)
    #[Route('/tags/list/{viewTemplate?}', name: 'mws_offer_tags')]
    public function tags(
        $viewTemplate,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response
    {
        $user = $this->getUser();

        if (!$user || ! $this->security->isGranted(MwsUser::$ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $keyword = $request->query->get('keyword', null);
        
        $qb = $mwsOfferStatusRepository->createQueryBuilder('t');

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                "searchKeyword" => $keyword,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsOfferStatusLookupType.json.twig"
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $filtersForm = $this->createForm(MwsSurveyJsType::class, $lastSearch);
        $filtersForm->handleRequest($request);

        if ($filtersForm->isSubmitted()) {
            $this->logger->debug("Did submit search form");

            if ($filtersForm->isValid()) {
                $this->logger->debug("Search form ok");
                // dd($filtersForm);

                $surveyAnswers = json_decode(
                    urldecode($filtersForm->get('jsonResult')->getData()),
                    true
                );
                $keyword = $surveyAnswers['searchKeyword'] ?? null;
                return $this->redirectToRoute(
                    'mws_offer_tags',
                    array_merge($request->query->all(), [
                        "viewTemplate" => $viewTemplate,
                        "keyword" => $keyword,
                        "page" => 1,
                    ]),
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        if ($keyword) {
            $qb
            ->andWhere("
                LOWER(REPLACE(t.slug, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
                OR LOWER(REPLACE(t.label, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
            ")
            ->setParameter('keyword', '%' . $keyword . '%');
        }

        $query = $qb->getQuery();
        // dd($query->getResult());    
        $tags = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $request->query->getInt('pageLimit', 10), /*page number*/
        );

        $this->logger->debug("Succeed to list offers");
        $slugToOfferTag = $mwsOfferStatusRepository->findAll();
        $slugToOfferTag = array_combine(array_map(function($t) {
            return $t->getSlug();
        }, $slugToOfferTag), $slugToOfferTag);

        return $this->render('@MoonManager/mws_offer/tags.html.twig', [
            'slugToOfferTag' => $slugToOfferTag,
            'tags' => $tags,
            'filtersForm' => $filtersForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/tag/edit/{categorySlug<[^/]*>}/{slug}/{viewTemplate}',
        name: 'mws_offer_tag_edit',
        methods: ['GET', 'POST'],
        defaults: [
            'viewTemplate' => null,
            'categorySlug' => null,
        ],
    )]
    public function tagEdit(
        string $slug,
        string $categorySlug,
        string|null $viewTemplate,
        Request $request,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
    ): Response
    {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user || ! $this->security->isGranted(MwsUser::$ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }
        $tag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
            $slug, $categorySlug,
        );
        if (!$tag) {
            throw $this->createNotFoundException("Unknow tag slug [$slug]");
        }

        $fType = MwsOfferStatusType::class;
        // $previousOwners = $mwsTargetUser->getTeamOwners()->toArray();
        // dd($viewTemplate);
        $form = $this->createForm($fType, $tag, [
            'shouldAddNew' => false,
            'targetTag' => $tag,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // dd($tag);
            // // TIPS : inverse of ManyToMany must be set manually :
            // foreach ($tag->getMwsOffers() as $key => $offer) {
            //     // https://www.doctrine-project.org/projects/doctrine-orm/en/2.16/reference/unitofwork-associations.html#important-concepts
            //     $offer->addTag($tag); // TODO : strange, not from setter ? maybe doctrine do not use setter ?
            //     $this->em->persist($offer);
            //     // dd($offer);
            // }

            $this->em->persist($tag);
            $this->em->flush();
            return $this->redirectToRoute('mws_offer_tags', [
                "filterTag" => $viewTemplate
            ], Response::HTTP_SEE_OTHER);
        }

        $slugToOfferTag = $mwsOfferStatusRepository->findAll();
        $slugToOfferTag = array_combine(array_map(function($t) {
            return $t->getSlug();
        }, $slugToOfferTag), $slugToOfferTag);

        return $this->render('@MoonManager/mws_offer/tagEdit.html.twig', [
            'tag' => $tag,
            'form' => $form,
            // TODO : remove code duplication, inject as global for 
            // all routes in this controller routes renderings with slugToOfferTag
            // AND all other redux indexes to send JS frontend side...
            'slugToOfferTag' => $slugToOfferTag,
            'viewTemplate' => $viewTemplate,
            'title' => "Modifier le tag {$tag->getLabel()}"
        ]);
    }

    #[Route('/tag/delete/{viewTemplate}',
        name: 'mws_offer_tag_delete',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagDelete(
        string|null $viewTemplate,
        Request $request,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsOfferRepository $mwsOfferRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response
    {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth with", [$request]);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-offer-tag-delete', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagSlug = $request->request->get('tagSlug');
        $tagCategorySlug = $request->request->get('tagCategorySlug');
        // TODO : DOC + validation, no tag category slug could use the 'null' keyword, now RESERVED...
        if ('null' === $tagCategorySlug) { 
            $tagCategorySlug = null; // Form data deserialisation stuff ? extracting as string instead of null...
        }
        $tag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
            $tagSlug, $tagCategorySlug
        );
        if (!$tag) {
            throw $this->createNotFoundException("Unknow tag slug [$tagSlug, $tagCategorySlug]");
        }
        $offerSlug = $request->request->get('offerSlug');
        $offer = $mwsOfferRepository->findOneBy([
            'slug' => $offerSlug,
        ]);
        if (!$offer) {
            throw $this->createNotFoundException("Unknow offer slug [$offerSlug]");
        }
        // dd($tag);
        // $tag->removeMwsOffer($offer);
        $offer->removeTag($tag); // TODO : MUST set inverse relation ship ? but no same issue with import ?

        // $this->em->persist($tag);
        $this->em->persist($offer);
        $this->em->flush();

        // https://stackoverflow.com/questions/47872020/symfony-4-how-to-add-csrf-token-without-building-form
        // // validate token
        // $csrf_token = new CsrfToken('my_token', 'generated_token_value_here');
        // if(!$csrfTokenManager->isTokenValid($csrf_token)) {
        //     //failed. do something.
        //     return new JsonResponse();
        // }
        // // generate token
        // $token = $csrfTokenManager->getToken('my_token')->getValue();
        // // refresh token
        // $csrfTokenManager->refreshToken('my_token');

        return $this->json([
            'newTags' => $offer->getTags(),
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-tag-delete')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/tag/add/{viewTemplate}',
        name: 'mws_offer_tag_add',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagAdd(
        string|null $viewTemplate,
        Request $request,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsOfferRepository $mwsOfferRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response
    {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth with", [$request]);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-offer-tag-add', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagSlug = $request->request->get('tagSlug');
        $tagCategorySlug = $request->request->get('tagCategorySlug');
        // TODO : DOC + validation, no tag category slug could use the 'null' keyword, now RESERVED...
        if ('null' === $tagCategorySlug) { 
            $tagCategorySlug = null; // Form data deserialisation stuff ? extracting as string instead of null...
        }
        $tag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
            $tagSlug, $tagCategorySlug
        );
        if (!$tag) {
            throw $this->createNotFoundException("Unknow tag slug [$tagSlug, $tagCategorySlug]");
        }
        $offerSlug = $request->request->get('offerSlug');
        $offer = $mwsOfferRepository->findOneBy([
            'slug' => $offerSlug,
        ]);
        if (!$offer) {
            throw $this->createNotFoundException("Unknow offer slug [$offerSlug]");
        }
        // dd($tag);
        $mwsOfferRepository->addTag($offer, $tag);

        $this->em->persist($offer);
        $this->em->flush();

        return $this->json([
            'newTags' => $offer->getTags(),
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-tag-delete')->getValue(),
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
            throw $this->createAccessDeniedException('Only for logged users');
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
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        SluggerInterface $slugger,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();
        if (!$user || ! $this->security->isGranted(MwsUser::$ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $maxTime = 60 * 30; // 30 minutes max
        set_time_limit($maxTime);
        ini_set('max_execution_time', $maxTime);

        $forceRewrite = $request->query->get('forceRewrite', false);
        $forceStatusRewrite = $request->query->get('forceStatusRewrite', false);
        $forceCleanTags = $request->query->get('forceCleanTags', false);
        
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
                    $offersDeserialized = $this->deserializeOffers(
                        $user, $importContent, $format, $newFilename,
                        $mwsOfferStatusRepository, $mwsOfferRepository
                    );
                    // dd($offersDeserialized);

                    $savedCount = 0;
                    /** @var MwsOffer $offer */
                    foreach ($offersDeserialized as $idx => $offer) {
                        $sourceName = $offer->getSourceName();
                        $slug = $offer->getSlug();
                        // $email = $offer->getContact1();
                        // $phone = $offer->getContact2();

                        // TODO : add as repository method ?
                        $qb = $mwsOfferRepository
                        ->createQueryBuilder('o')
                        ->where('o.slug = :slug')
                        ->andWhere('o.sourceName = :sourceName')
                        ->setParameter('slug', $slug)
                        ->setParameter('sourceName', $sourceName);
                        // if ($email && strlen($email)) {
                        //     $qb->andWhere('p.email = :email')
                        //     ->setParameter('email', trim(strtolower($email)));
                        // }
                        // if ($phone && strlen($phone)) {
                        //     $qb->andWhere('p.phone = :phone')
                        //     ->setParameter('phone', trim(strtolower($phone)));
                        // }
                        $query = $qb->getQuery();

                        // dd($query->getDQL());
                        // dd($query);
                        $allDuplicates = $query->execute();
                        
                        // dd($allDuplicates);
                        // var_dump($allDuplicates);exit;
                        if ($allDuplicates && count($allDuplicates)) {
                            if ($forceRewrite) {
                                $reportSummary .= "<strong>Surcharge le doublon : </strong> [$sourceName , $slug]<br/>";
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
                                $sync('clientUsername');
                                $sync('contact1');
                                $sync('contact2');
                                $sync('contact3');
                                $sync('title');
                                $sync('description');
                                $sync('budget');
                                $sync('leadStart');
                                $sync('sourceUrl');
                                $sync('clientUrl');
                                $sync('currentBillingNumber');
                                $sync('sourceDetail');
                                if ($forceStatusRewrite) {
                                    $sync('currentStatusSlug');
                                }
                                if ($forceCleanTags) {
                                    $offer->getTags()->clear();
                                }

                                $tags = $inputOffer->getTags();
                                foreach ($tags as $tag) {
                                    // TIPS : inside addTag, only one by specific only one choice category type ?
                                    // OK to copy all, clone src use
                                    // $mwsOfferRepository->addTag($offer, $tag);
                                    $offer->addTag($tag);
                                }

                                // dump($inputOffer);
                                // dd($offer);
                                // CLEAN all possible other duplicates :
                                foreach ($allDuplicates as $otherDups) {
                                    $em->remove($otherDups);
                                }
                            } else {
                                $reportSummary .= "<strong>Ignore le doublon : </strong> [$sourceName,  $slug]<br/>";
                                continue;
                            }
                        }

                        // TODO : add comment to some traking entities, column 'Observations...' or too huge for nothing ?

                        // if (!$offer->getSourceFile()
                        // || !strlen($offer->getSourceFile())) {
                        //     $offer->setSourceFile('unknown');
                        // }

                        // Default auto compute values :
                        /** @var MwsOffer $offer */
                        if (!$offer->getCurrentStatusSlug()) {
                            // TODO : from .env config file ? or from DB ? (status with tags for default selections ?)
                            $offerStatusSlug = "a-relancer"; // TODO : load from status DB or config DB...
                            $offer->setCurrentStatusSlug($offerStatusSlug);
                            // dd($offer);    
                        }

                        $em->persist($offer);
                        $em->flush();
                        $savedCount++;
                    }
                    $reportSummary .= "<br/><br/>Enregistrement de $savedCount offres OK <br/>";

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
    public function offerSlugToSourceUrlTransformer($sourceName) {
        return [ // TODO : from configs or services ?
            'source.test.localhost' => function ($oSlug) {
                return "http://source.test.localhost/offers/$oSlug";
            },
        ][$sourceName] ?? function($oSlug) use ($sourceName) {
            return "https://$sourceName/projects/$oSlug";
        };
    }
    public function usernameToClientUrlTransformer($sourceName) {
        return [ // TODO : from configs or services ?
            'source.test.localhost' => function ($username) {
                return "http://source.test.localhost/-$username";
            },
        ][$sourceName] ?? function($oSlug) use ($sourceName) {
            return "https://$sourceName/-$oSlug";
        };
    }

    // TODO : more like 'loadOffers' than deserialize,
    //        will save in db too for code factorisation purpose...
    public function deserializeOffers($user, $data, $format, $sourceFile, $mwsOfferStatusRepository, $mwsOfferRepository) {
        /** @param MwsOffer[] **/
        $out = null;
        // TODO : add custom serializer format instead of if switch ?
        if ($format === 'monwoo-extractor-export') {
            $data = json_decode($data, true);
            $out = [];

            // $sources = array_keys($data);
            foreach ($data as $sourceSlug => $board) {
                // foreach ($board['users'] as $contactSlug => $c) {
                //     # TODO : add contacts (how to send back ? inside _contact props ?)
                // }
                // dump($board);
                $contactIndex = $board['users'] ?? [];
                foreach (($board['projects'] ?? []) as $offerSlug => $o) {
                    $offer = new MwsOffer($this->em);

                    $cleanUp = function($val) {
                        // NO-BREAK SPACE (U+A0)
                        // https://stackoverflow.com/questions/150033/regular-expression-to-match-non-ascii-characters
                        // https://stackoverflow.com/questions/55904971/regex-in-php-returning-preg-match-compilation-failed-pcre-does-not-support
                        // https://stackoverflow.com/questions/40724543/how-to-replace-decoded-non-breakable-space-nbsp/40724830#40724830
                        // return $val;
                        // return trim(preg_replace('/[\s\xc2\xa0]+/', ' ', $val));
                        return trim(
                            // NO-BREAK SPACE (U+A0)
                            preg_replace('/(\xc2\xa0)+/', ' ',
                                // preg_replace('/\s+/', ' ', $val)
                                // https://stackoverflow.com/questions/3469080/match-whitespace-but-not-newlines
                                // preg_replace('/\h+/', ' ', $val)
                                preg_replace('/[^\S\r\n]+/', ' ', $val)
                                // $val
                            )
                        );
                        // return trim(preg_replace('/[\s\xc2-\xa0]+/', ' ', $val));
                    };
                    $userId = $o["uId"] ?? null;
                    $contactBusinessUrl = $this
                    ->usernameToClientUrlTransformer($sourceSlug)($userId);
                    // TODO : check ? : https://symfonycasts.com/screencast/symfony-doctrine/sluggable
                    $offer->setSlug(strtolower($this->slugger->slug(
                        $sourceSlug . '-' . $offerSlug
                    )));
                    $offer->setClientUsername($userId);
                    $offer->setContact1($cleanUp($o["email"]));
                    $offer->setContact2($cleanUp($o["tel"]));
                    $offer->setTitle($cleanUp($o["title"]));
                    $offer->setDescription($cleanUp($o["description"]));
                    $offer->setBudget($cleanUp($o["projectBudget"]));
                    $leadStartRaw = $o["projectPublicationDate"];
                    // fix possible tipings issues
                    $leadStartRaw = trim(preg_replace('/\s+/', ' ', $leadStartRaw));
                    $formatter = new IntlDateFormatter(
                        "fr_FR",
                        // IntlDateFormatter::RELATIVE_FULL,
                        // IntlDateFormatter::SHORT,
                        IntlDateFormatter::NONE,
                        IntlDateFormatter::NONE,
                        'Europe/Paris',
                        IntlDateFormatter::TRADITIONAL,
                        // 'D MMMM YYYY à HH:mm'
                        // https://bugs.php.net/bug.php?id=53939
                        // 'D MMMM YYYY à HH\\hmm'
                        // "dd MMMM YYYY à HH'h'mm",
                        // https://www.the-art-of-web.com/php/intl-date-formatter/
                        // https://unicode-org.github.io/icu/userguide/format_parse/datetime/
                        "d MMMM yyyy 'à' HH'h'mm",
                    );
                    $leadStart = (new DateTime())->setTimestamp(
                        $formatter->parse($leadStartRaw)
                    );
                    // $leadStartTxt = $leadStart->format('Y-m-d H:i:s');
                    $leadStartTxt = $formatter->format($leadStart);
                    $this->logger->debug("Offer LeadStart from [$leadStartRaw] to [$leadStartTxt]");

                    $sourceStatus = $cleanUp($o["projectStatus"]);
                    $sourceStatusSlug = strtolower($this->slugger->slug($sourceStatus));
                    $sourceCategoryLabel = 'mws.offer.tags.category.src-import';
                    $sourceCategorySlug = strtolower($this->slugger->slug($sourceCategoryLabel));
                    $sourceTag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
                        $sourceStatusSlug, $sourceCategorySlug
                    );
                    // dd($sourceTag);
                    if (!$sourceTag) {
                        $sourceCategory = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
                            $sourceCategorySlug, null
                        );
                        if (!$sourceCategory) {
                            $sourceCategory = new MwsOfferStatus();
                            $sourceCategory->setSlug($sourceCategorySlug);
                            $sourceCategory->setLabel($sourceCategoryLabel);
                            // $sourceCategory->setCategoryOkWithMultiplesTags(true);
                            // $sourceTag->setCategorySlug($TODO);
                            // TIPS : NEED to PERSIST AND FLUSH for next findOneBy to work :
                            $this->em->persist($sourceCategory);
                        }    
                        $sourceTag = new MwsOfferStatus();
                        $sourceTag->setSlug($sourceStatusSlug);
                        $sourceTag->setLabel($sourceStatus);
                        $sourceTag->setCategorySlug($sourceCategorySlug);
                        // TIPS : NEED to PERSIST AND FLUSH for next findOneBy to work :
                        $this->em->persist($sourceTag);
                        $this->em->flush();
                    }
                    $offerStatusSlug = $sourceStatusSlug; // TODO : load from status DB or config DB...

                    $mwsOfferRepository->addTag($offer, $sourceTag);

                    $offer->setLeadStart($leadStart);
                    // $offer->setContact3($o["..."]);
                    $offer->setSourceUrl(
                        $this->offerSlugToSourceUrlTransformer($sourceSlug)($offerSlug)
                    );
                    $offer->setClientUrl($contactBusinessUrl);
                    $offer->setCurrentStatusSlug($offerStatusSlug);
                    // $offer->setTitle($o['title']);
                    $offer->setSourceName($sourceSlug);
                    $offer->setSourceDetail($o);

                    // TODO : getClientSlug that ensure contact unicity ?

                    $c = $contactIndex[$userId] ?? [];
                    // TODO : contactSlug = $c .... ; => unicity check in db before creating new one...
                    // TODO : from repo ? need update if exist or keep existing ?
                    $contact = new MwsContact();

                    // TODO : from ORM update listeners instead of hard coded ? but how to inject comments etc... ?
                    $traking = new MwsContactTracking();
                    $traking->setContact($contact);
                    $traking->setOwner($user);
                    $traking->setComment("Imported from : $sourceFile");
                    $contact->addMwsContactTracking($traking);
                    
                    $contact->setUsername($userId);
                    $contact->setStatus($c['status']);
                    $contact->setPostalCode($c['adresseL2_CP']);
                    $contact->setCity($c['adresseL2_ville']);
                    $contact->setAvatarUrl($c['photoUrl']);
                    // TODO : data check and re-transform, tel might switch with email in source file...
                    if ($c['email'] ?? null) {
                        $contact->setEmail($c['email']);
                    }
                    if ($c['tel'] ?? null) {
                        $contact->setPhone($c['tel']);
                    }
                    $contact->setBusinessUrl($contactBusinessUrl);
                    $contact->setSourceName($sourceSlug);
                    $contact->setSourceDetail($c);

                    $offer->addContact($contact);

                    // TODO : from ORM update listeners instead of hard coded ?
                    $traking = new MwsOfferTracking();
                    $traking->setOffer($offer);
                    $traking->setOwner($user);
                    $traking->setComment("Imported from : $sourceFile");
                    $traking->setOfferStatusSlug($offerStatusSlug);
                    $offer->addMwsOfferTracking($traking);

                    $out[] = $offer;
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
