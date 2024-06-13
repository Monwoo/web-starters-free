<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Entity\MwsContact;
use MWS\MoonManagerBundle\Entity\MwsContactTracking;
use MWS\MoonManagerBundle\Entity\MwsMessage;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;
use MWS\MoonManagerBundle\Entity\MwsOfferTracking;
use MWS\MoonManagerBundle\Entity\MwsTimeTag;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsOfferImportType;
use MWS\MoonManagerBundle\Form\MwsOfferStatusType;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Form\MwsUserFilterType;
use MWS\MoonManagerBundle\Repository\MwsContactRepository;
use MWS\MoonManagerBundle\Repository\MwsMessageRepository;
use MWS\MoonManagerBundle\Repository\MwsOfferRepository;
use MWS\MoonManagerBundle\Repository\MwsOfferStatusRepository;
use MWS\MoonManagerBundle\Repository\MwsOfferTrackingRepository;
use MWS\MoonManagerBundle\Repository\MwsTimeTagRepository;
use MWS\MoonManagerBundle\Repository\MwsUserRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(
    '/{_locale<%app.supported_locales%>}/mws-offer',
    options: ['expose' => true],
)]
#[SecuAttr(
    "is_granted('ROLE_USER')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
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
    ) {
    }

    #[Route('/', name: 'mws_offer')]
    public function index(
        Request $request,
    ): Response {
        // TODO : depending of user roles : will have different preview systems
        return $this->redirectToRoute(
            'mws_offer_lookup',
            array_merge($request->query->all(), [
                "page" => 1,
                // "filterTags" => $filterTags,
                // "keyword" => $keyword
            ]),
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route(
        '/offer/sync/{viewTemplate<[^/]*>?}',
        name: 'mws_offer_sync',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function offerSync(
        string|null $viewTemplate,
        Request $request,
        MwsOfferRepository $mwsOfferRepository,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsContactRepository $mwsContactRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth", []);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-offer-sync', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $offerInput = $request->request->get('offer');
        // TODO : use serializer deserialize ?
        $offerInput = json_decode($offerInput, true);
        // dd($offerInput);
        $tagSlugSep = ' > '; // TODO :load objects and trick display/value function of surveyJS instead...

        $criteria = [];
        if ($offerInput['id'] ?? false) {
            $criteria['id'] = $offerInput['id'];
        }
        $offer = count($criteria)
            ? $mwsOfferRepository->findOneBy($criteria)
            : null;
        if (!$offer) {
            $offer = new MwsOffer();
        }
        if ($offerInput['_shouldDelete'] ?? false) {
            $this->em->remove($offer);
            $this->em->flush();
            return $this->json([
                'sync' => [
                    'id' => $offerInput['id'] ?? null,
                    '_haveBeenDeleted' => true,
                ],
                'didDelete' => true,
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-sync')->getValue(),
                'viewTemplate' => $viewTemplate,
            ]);
        }
        $sync = function ($path, $setter = null) use (&$offer, &$offerInput) {
            $get = 'get' . ucfirst($path);
            $v =  $offerInput[$path] ?? null;
            if (null !== $v) {
                $set = 'set' . ucfirst($path);
                if (!method_exists($offer, $set)) {
                    // Is collection :
                    $add = 'add' . ucfirst($path);
                    if (!method_exists($offer, $add)) {
                        $add = preg_replace('/s$/', '', $add);
                    }
                    $collection = $offer->$get();
                    $collection->clear();
                    foreach ($v as $subV) {
                        if ($setter) {
                            $setter($offer, $subV);
                        } else {
                            $offer->$add($subV);
                        }
                    }
                } else {
                    if ($setter) {
                        $setter($offer, $v);
                    } else {
                        $offer->$set($v);
                    }
                }
            }
        };

        // if (is_array($qualifInput['primaryColorRgb'] ?? false)) {
        //     $qualifInput['primaryColorRgb'] = implode(
        //         ', ',
        //         $qualifInput['primaryColorRgb']
        //     );
        // }
        // if ($offerInput['leadStart']) {
        //     $offerInput['leadStart'] = new DateTime();
        // } else {
        //     $offerInput['leadStart'] = new DateTime();
        // }
        $offerInput['leadStart'] = new DateTime(
            ($offerInput['leadStart'] ?? false)
                ? $offerInput['leadStart']
                : 'now'
        );
        $sync('leadStart');
        $offerInput['slug'] = $this->slugger->slug($offerInput['slug']);
        $sync('slug');
        $sync('sourceName');
        $sync('clientUsername');
        $sync('contact1');
        $sync('contact2');
        $sync('contact3');
        $sync('sourceUrl');
        $sync('clientUrl');
        $sync('currentBillingNumber');
        // $offerInput['currentStatusSlug'] = ($offerInput['currentStatusSlug'] ?? false)
        //     ? str_replace($tagSlugSep, '|', $offerInput['currentStatusSlug'])
        //     : null;
        // $sync('currentStatusSlug');
        $offerInput['tags'] = $offerInput['tags'] ?? [];
        if ($offerInput['currentStatusSlug'] ?? false) {
            $offerInput['currentStatusSlug'] =
                str_replace($tagSlugSep, '|', $offerInput['currentStatusSlug']);
            // $sync('currentStatusSlug');
            $offerInput['tags'][] = str_replace('|', $tagSlugSep, $offerInput['currentStatusSlug']);
        }
        $sync('currentStatusSlug');

        foreach ($offerInput['tags'] as $idx => $tag) {
            if (is_string($tag)) {
                [$categorySlug, $slug] = explode($tagSlugSep, str_replace('|', $tagSlugSep, $tag));
                $offerInput['tags'][$idx] = $mwsOfferStatusRepository->findOneBy([
                    "categorySlug" => $categorySlug,
                    "slug" => $slug,
                ]);
            }
        }
        // dd($offerInput['tags']);
        $sync('tags', function ($o, $v) use ($mwsOfferRepository) {
            $mwsOfferRepository->addTag($o, $v);
            // dd($o);
        });
        // TODO : ensure tags have ARRAY serialization,  
        // $mwsOfferRepository->addTag is giving OBJECT instead of array 
        // when editing tags from offer edit popup
        // (PHP indexed array will be exported as object instead of array ?):
        $tags = $offer->getTags()->toArray();
        $offer->getTags()->clear();
        foreach ($tags as $idx => $tag) {
            # code...
            $offer->addTag($tag);
        }
        // dd($offer);

        // TIMING TAGS :
        // dd($offerInput['timingTags']);
        $offerInput['timingTags'] = $offerInput['timingTags'] ?? [];
        foreach ($offerInput['timingTags'] as $idx => $tTagSlug) {
            $tTag = $mwsTimeTagRepository->findOneBy([
                'slug' => $tTagSlug
            ]);
            $offerInput['timingTags'][$idx] = $tTag;
            // if (!$tTag) {
            //     // TODO : create non existent timing TAGS ? ignore for now if not exist...
            //     continue; // NO need, null value are already ignored
            // }
            // $offer->addTimingTag($tTag);
        }
        // dd($offerInput['timingTags']);
        $sync('timingTags');
        // dd($offer);

        if (($offerInput['sourceDetail'] ?? false)
            && $offerInput['sourceDetail'][0]
        ) {
            // TODO : using paneldynamic in surveyJS, need array as input, other way to avoid array wrapings
            $offerInput['sourceDetail'] = $offerInput['sourceDetail'][0];
        }

        if (($offerInput['sourceDetail'] ?? false)
            && ($offerInput['sourceDetail']['messages'] ?? false)
        ) {
            // Re-map format used by SurveyJS to allow list of items...
            $offerInput['sourceDetail']['messages'] = array_map(function ($m) {
                return $m['msg'] ?? $m;
            }, $offerInput['sourceDetail']['messages']);
        }
        $sync('sourceDetail');
        // dd($offer);

        foreach ($offerInput['contacts'] ?? [] as $idx => $c) {
            if (!$c instanceof MwsContact) {
                // TODO : fetch / sync or add contacts ?
                // dd($c);
                $contact = null;
                if ($c['id'] ?? false) {
                    $contact = $mwsContactRepository->findOneBy([
                        'id' => $c['id'],
                    ]);
                }
                if (!$contact) {
                    $contact = new MwsContact();
                }
                $contactSync = function ($path, $setter = null) use (&$contact, &$c) {
                    $v =  $c[$path] ?? null;
                    if (null !== $v) {
                        $set = 'set' . ucfirst($path);
                        if ($setter) {
                            $setter($contact, $v);
                        } else {
                            $contact->$set($v);
                        }
                    }
                };
                $contactSync('username');
                $contactSync('status');
                $contactSync('postalCode');
                $contactSync('city');
                $contactSync('avatarUrl');
                $contactSync('email');
                $contactSync('phone');
                $contactSync('sourceDetail');
                $contactSync('sourceName');
                $contactSync('businessUrl');

                // TODO : ensure mwsOffers OK or have duplicates ?
                $contact->addMwsOffer($offer);

                // TODO : update mwsContactTrackings ? or useless if not used for business purpose, heavy data for nothing ? all already tracked at offer levels with human comment enough ? 
                // TODO : update mwsUsers ? or useless if not used for business purpose, heavy data for nothing ? all already tracked at offer levels with human comment enough ? 
                // dd($contact);
                $this->em->persist($contact);
            }
        }
        // $sync('contacts');

        $this->em->persist($offer);
        $this->em->flush();

        return $this->json([
            'sync' => $offer,
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-sync')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/lookup/{viewTemplate<[^/]*>?}', name: 'mws_offer_lookup')]
    public function lookup(
        $viewTemplate,
        MwsOfferRepository $mwsOfferRepository,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsMessageRepository $mwsMessageRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        $user = $this->getUser();
        $tagSlugSep = ' > '; // TODO :load objects and trick display/value function of surveyJS instead...

        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $requestData = $request->query->all();
        // dd($requestData);
        // After symfony 6, get will not return array anymore
        // $searchTags = $request->query->get('tags', null); // []);
        $keyword = $requestData['keyword'] ?? null;
        $searchBudgets = $requestData['searchBudgets'] ?? null;
        $searchStart = $requestData['searchStart'] ?? null;
        $searchEnd = $requestData['searchEnd'] ?? null;

        $searchTags = $requestData['searchTags'] ?? []; // []);
        $searchTagsToInclude = $requestData['searchTagsToInclude'] ?? []; // []);
        $searchTagsToAvoid = $requestData['searchTagsToAvoid'] ?? []; // []);

        // dd($searchTagsToAvoid);

        $customFilters = $requestData['customFilters'] ?? [];
        // $sourceRootLookupUrl = $requestData['sourceRootLookupUrl'] ?? null;
        // dd($searchTags);

        $qb = $mwsOfferRepository->createQueryBuilder('o');

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                "searchKeyword" => $keyword,
                "searchBudgets" => $searchBudgets,
                "searchStart" => $searchStart,
                "searchEnd" => $searchEnd,
                "searchTags" => $searchTags,
                "searchTagsToInclude" => $searchTagsToInclude,
                "searchTagsToAvoid" => $searchTagsToAvoid,
                "customFilters" => $customFilters,
                // "sourceRootLookupUrl" => $sourceRootLookupUrl,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsOfferLookupType.json.twig",
                [
                    'allOfferTags' => array_map(
                        function (array $tagResp) use ($tagSlugSep) {
                            $tag = $tagResp[0];
                            // dd($tagResp);
                            return $tag->getCategorySlug() . $tagSlugSep . $tag->getSlug();
                        },
                        // $mwsOfferStatusRepository->findAll()
                        $mwsOfferStatusRepository
                            ->createQueryBuilder("t")
                            // https://stackoverflow.com/questions/8233746/concatenate-with-null-values-in-sql
                            ->select("t, CONCAT(COALESCE(t.categorySlug, ''), t.slug) AS slugKey")
                            // ->where($qb->expr()->isNotNull("t.categorySlug"))
                            // ->orderBy("t.categorySlug")
                            // ->addOrderBy("t.slug")
                            ->addOrderBy("slugKey")
                            ->getQuery()->getResult()
                    ),
                    'allOfferBudgets' =>
                    // explode(
                    // ',', // TODO : split on , migh clash if budget use , inside labels...
                    // dd(
                    $mwsOfferRepository
                        ->createQueryBuilder("o")
                        ->select("
                            DISTINCT o.budget as value
                        ")
                        ->orderBy('o.budget', 'ASC')
                        ->getQuery()->getResult()
                    // [0]['budgets'] ?? ''
                    // )
                    // ),
                ]
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
                $searchBudgets = $surveyAnswers['searchBudgets'] ?? null;
                $searchStart = $surveyAnswers['searchStart'] ?? null;
                $searchEnd = $surveyAnswers['searchEnd'] ?? null;

                $searchTags = $surveyAnswers['searchTags'] ?? []; // []);
                $searchTagsToInclude = $surveyAnswers['searchTagsToInclude'] ?? []; // []);
                $searchTagsToAvoid = $surveyAnswers['searchTagsToAvoid'] ?? []; // []);

                $customFilters = $surveyAnswers['customFilters'] ?? [];
                // dd($searchTags);
                // $sourceRootLookupUrl = $surveyAnswers['sourceRootLookupUrl'] ?? null;
                return $this->redirectToRoute(
                    'mws_offer_lookup',
                    array_merge($request->query->all(), [
                        "viewTemplate" => $viewTemplate,
                        "keyword" => $keyword,
                        "searchBudgets" => $searchBudgets,
                        "searchStart" => $searchStart,
                        "searchEnd" => $searchEnd,
                        "searchTags" => $searchTags,
                        "searchTagsToInclude" => $searchTagsToInclude,
                        "searchTagsToAvoid" => $searchTagsToAvoid,
                        "customFilters" => $customFilters,
                        "page" => 1,
                        // "sourceRootLookupUrl" => $sourceRootLookupUrl,
                    ]),
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        // TIPS : global form, better use api stuff instead of copy/past code inside each controllers...
        // // $offer =
        // $mwsAddOfferForm = $mwsOfferRepository->fetchMwsAddOfferForm(
        //     /* TODO: init with offer id */);
        // $mwsAddOfferForm->handleRequest($request);
        // if ($mwsAddOfferForm->isSubmitted()) {
        //     $this->logger->debug("Did submit add offer form");
        //     if ($mwsAddOfferForm->isValid()) {
        //         $this->logger->debug("add offer form ok");
        //         $surveyAnswers = json_decode(
        //             urldecode($mwsAddOfferForm->get('jsonResult')->getData()),
        //             true
        //         );
        //         dd($surveyAnswers);
        //         return $this->redirectToRoute(
        //             'mws_offer_lookup',
        //             array_merge($request->query->all(), [
        //                 "viewTemplate" => $viewTemplate,
        //                 "keyword" => $keyword,
        //                 "searchBudgets" => $searchBudgets,
        //                 "searchStart" => $searchStart,
        //                 "searchEnd" => $searchEnd,
        //                 "searchTags" => $searchTags,
        //                 "searchTagsToInclude" => $searchTagsToInclude,
        //                 "searchTagsToAvoid" => $searchTagsToAvoid,
        //                 "customFilters" => $customFilters,
        //                 "page" => 1,
        //                 // "sourceRootLookupUrl" => $sourceRootLookupUrl,
        //             ]),
        //             Response::HTTP_SEE_OTHER
        //         );
        //     }
        // }

        $mwsOfferRepository->applyOfferLokup($qb, [
            "keyword" => $keyword,
            "searchBudgets" => $searchBudgets,
            "searchStart" => $searchStart,
            "searchEnd" => $searchEnd,
            "searchTags" => $searchTags,
            "searchTagsToInclude" => $searchTagsToInclude,
            "searchTagsToAvoid" => $searchTagsToAvoid,
            "customFilters" => $customFilters,
        ]);

        $query = $qb->getQuery();
        // dd($query->getResult());    
        $offers = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $request->query->getInt('pageLimit', 10), /*page number*/
        );

        $this->logger->debug("Succeed to list offers");
        // dd($offers);
        $offerTagsByCatSlugAndSlug = $mwsOfferStatusRepository->getTagsByCategorySlugAndSlug();

        // $availableTemplates = $mwsMessageRepository->findBy([
        //     "isTemplate" => true,
        // ]);
        // TODO : helper inside repository ?, factorize code
        // TODO : remove code duplication
        $availableTQb = $mwsMessageRepository
            ->createQueryBuilder('m')
            ->where('m.isTemplate = :isTemplate')
            ->setParameter('isTemplate', true)
            ->orderBy('m.templateCategorySlug')
            ->addOrderBy('m.templateNameSlug');
        $availableTemplates = $availableTQb->getQuery()->execute();
        // https://surveyjs.answerdesk.io/ticket/details/t12135/how-to-get-full-choise-object-when-the-value-in-dropdown-selected
        // https://surveyjs.io/form-library/documentation/api-reference/survey-data-model#onLoadChoicesFromServer
        // https://surveyjs.io/form-library/examples/dropdown-box-with-custom-items/reactjs#content-code

        // $availableTQb = $mwsMessageRepository
        // ->createQueryBuilder('m')
        // ->select('m.templateNameSlug')
        // ->where('m.isTemplate = :isTemplate')
        // ->setParameter('isTemplate', true);
        // $availableTemplateNameSlugs = array_map(function(MwsMessage $o) {
        //     return $o->getTemplateNameSlug();
        // }, $availableTemplates);
        $availableTemplateNameSlugs = array_reduce(
            $availableTemplates,
            function ($acc, MwsMessage $o) {
                $slug = $o->getTemplateNameSlug();
                // if (!in_array($slug, $acc, true)) { + insertionSort...
                if (!in_array($slug, $acc)) {
                    $acc[] = $slug;
                }
                return $acc;
            },
            []
        );
        $availableTemplateCategorySlugs = array_reduce(
            $availableTemplates,
            function ($acc, MwsMessage $o) {
                $slug = $o->getTemplateCategorySlug();
                if (!in_array($slug, $acc)) {
                    $acc[] = $slug;
                }
                return $acc;
            },
            []
        );

        // TIPS : bad idea : autocomplet will re-order on
        // function best match... + losing index remove full array feature ?
        // natsort($availableTemplateNameSlugs);
        // natsort($availableTemplateCategorySlugs);
        // dd($availableTemplateCategorySlugs);
        // dd($availableTemplateNameSlugs);

        // TODO : from config file or user param configs ?
        $availableMonwooAmountType = [
            "Pour le projet",
            "Par jour",
        ];

        $addMessageConfig = [
            // "jsonResult" => rawurlencode(json_encode([])),
            "jsonResult" => rawurlencode('{}'),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsMessageType.json.twig",
                [
                    "availableTemplates" => $availableTemplates,
                    "availableTemplateNameSlugs" => $availableTemplateNameSlugs,
                    "availableMonwooAmountType" => $availableMonwooAmountType,
                    "availableTemplateCategorySlugs" => $availableTemplateCategorySlugs,
                ]
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $addMessageForm = $this->createForm(MwsSurveyJsType::class, $addMessageConfig, [
            // ensure query param 
            'action' => $this->generateUrl('mws_message_list', [
                'viewTemplate' => $viewTemplate,
                'backUrl' => $this->generateUrl('mws_offer_lookup', array_merge($request->query->all(), [
                    'viewTemplate' => $viewTemplate,
                ])),
            ])
        ]);

        // dd($offers->getItems());
        // TODO : also crossable with message sources,
        // same id from multiple source may not always be equal for same stuff behing...
        $messagesByProjectId = $mwsMessageRepository->getMessagesByProjectIdFromOffers(
            $offers->getItems()
        );
        // dd($messagesByProjectId);
        return $this->render('@MoonManager/mws_offer/lookup.html.twig', [
            'offerTagsByCatSlugAndSlug' => $offerTagsByCatSlugAndSlug,
            'offers' => $offers,
            'messagesByProjectId' => $messagesByProjectId,
            'lookupForm' => $filterForm,
            'viewTemplate' => $viewTemplate,
            'addMessageForm' => $addMessageForm,
        ]);
    }

    #[Route('/view/{offerSlug}/{viewTemplate<[^/]*>?}', name: 'mws_offer_view')]
    public function view(
        $offerSlug,
        $viewTemplate,
        MwsOfferRepository $mwsOfferRepository,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsMessageRepository $mwsMessageRepository,
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $offer = $mwsOfferRepository->findOneBy([
            'slug' => $offerSlug
        ]);
        if (!$offer) {
            throw $this->createNotFoundException("Unknow offer slug [$offerSlug]");
        }

        // TODO : factorize with repo and remove code duplication :
        $availableTQb = $mwsMessageRepository
            ->createQueryBuilder('m')
            ->where('m.isTemplate = :isTemplate')
            ->setParameter('isTemplate', true)
            ->orderBy('m.templateCategorySlug')
            ->addOrderBy('m.templateNameSlug');
        $availableTemplates = $availableTQb->getQuery()->execute();
        $availableTemplateNameSlugs = array_reduce(
            $availableTemplates,
            function ($acc, MwsMessage $o) {
                $slug = $o->getTemplateNameSlug();
                if (!in_array($slug, $acc)) {
                    $acc[] = $slug;
                }
                return $acc;
            },
            []
        );
        $availableTemplateCategorySlugs = array_reduce(
            $availableTemplates,
            function ($acc, MwsMessage $o) {
                $slug = $o->getTemplateCategorySlug();
                if (!in_array($slug, $acc)) {
                    $acc[] = $slug;
                }
                return $acc;
            },
            []
        );

        // TODO : from config file or user param configs ?
        $availableMonwooAmountType = [
            "Pour le projet",
            "Par jour",
        ];

        $addMessageConfig = [
            // "jsonResult" => rawurlencode(json_encode([])),
            "jsonResult" => rawurlencode('{}'),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsMessageType.json.twig",
                [
                    "availableTemplates" => $availableTemplates,
                    "availableTemplateNameSlugs" => $availableTemplateNameSlugs,
                    "availableMonwooAmountType" => $availableMonwooAmountType,
                    "availableTemplateCategorySlugs" => $availableTemplateCategorySlugs,
                ]
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $addMessageForm = $this->createForm(MwsSurveyJsType::class, $addMessageConfig, [
            // ensure query param 
            'action' => $this->generateUrl('mws_message_list', [
                'viewTemplate' => $viewTemplate,
                'backUrl' => $this->generateUrl('mws_offer_lookup', array_merge($request->query->all(), [
                    'viewTemplate' => $viewTemplate,
                ])),
            ])
        ]);

        $messagesByProjectId = $mwsMessageRepository->getMessagesByProjectIdFromOffers(
            [$offer]
        );

        $offerTagsByCatSlugAndSlug = $mwsOfferStatusRepository->getTagsByCategorySlugAndSlug();
        return $this->render('@MoonManager/mws_offer/view.html.twig', [
            'offerTagsByCatSlugAndSlug' => $offerTagsByCatSlugAndSlug,
            'offer' => $offer,
            'viewTemplate' => $viewTemplate,
            'addMessageForm' => $addMessageForm,
            'messagesByProjectId' => $messagesByProjectId,
        ]);
    }

    #[Route(
        '/add-comment/{viewTemplate<[^/]*>?}',
        name: 'mws_offer_add_comment',
        methods: ['POST'],
    )]
    public function addComment(
        string|null $viewTemplate,
        Request $request,
        MwsOfferRepository $mwsOfferRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) { // TODO : only for admin too ?
            $this->logger->debug("Fail auth with", [$request]);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-offer-add-comment', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $offerSlug = $request->request->get('offerSlug');
        $offer = $mwsOfferRepository->findOneBy([
            'slug' => $offerSlug,
        ]);
        if (!$offer) {
            throw $this->createNotFoundException("Unknow offer slug [$offerSlug]");
        }

        $tracking = new MwsOfferTracking();
        $tracking->setOffer($offer);
        $tracking->setOwner($user);

        $comment = $request->request->get('comment', '--');
        // if (!$comment) {
        //     throw $this->createNotFoundException("Missing comment");
        // }
        $tracking->setComment($comment);

        $offerStatusSlug = $request->request->get('offerStatusSlug', '--');
        if ($offerStatusSlug && strlen($offerStatusSlug && 'null' !== $offerStatusSlug)) {
            $offer->setCurrentStatusSlug($offerStatusSlug);
            $this->em->persist($offer); // will not reflet changes in offer returned if created from modal with minimum info... ? nop, sound ok this way, ORM links take care of it...
            $tracking->setOfferStatusSlug($offerStatusSlug);
        }

        // $this->em->persist($offer);
        $this->em->persist($tracking);
        $this->em->flush(); // TIPS : Sync Generated ID in DB for new tracking

        // No need of below, inverse relationship of setOffer take care of it
        // $offer->addMwsOfferTracking($tracking);
        // $this->em->persist($offer);
        // $this->em->flush();

        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return $this->json([
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-add-comment')->getValue(),
                'viewTemplate' => $viewTemplate,
                'sync' => $offer,
            ]);
        }
        return $this->redirectToRoute(
            'mws_offer_lookup',
            [ // array_merge($request->query->all(), [
                "viewTemplate" => $viewTemplate,
                "page" => 1,
            ], //),
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route(
        '/delete-tracking/{viewTemplate<[^/]*>?}',
        name: 'mws_offer_delete_tracking',
        methods: ['POST'],
    )]
    public function deleteTracking(
        string|null $viewTemplate,
        Request $request,
        MwsOfferTrackingRepository $mwsOfferTrackingRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            // TODO : also allow any user to remove SELF comment 
            // if at most 10 minutes after post, admin will have to review otherwise
            throw $this->createAccessDeniedException('Only for admins');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-offer-delete-tracking', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $trackingId = $request->request->get('trackingId');
        if (!$trackingId) {
            throw $this->createAccessDeniedException('Missing trackingId');
        }

        /** @var MwsOfferTracking $tracking */
        $tracking = $mwsOfferTrackingRepository->findOneBy([
            'id' => $trackingId
        ]);
        if (!$tracking) {
            throw $this->createAccessDeniedException("Unknown tracking id [$trackingId]");
        }
        $offer = $tracking->getOffer();

        $this->em->remove($tracking);
        $this->em->flush(); // TIPS : Sync Generated ID in DB for new tracking

        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return $this->json([
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-delete-tracking')->getValue(),
                'viewTemplate' => $viewTemplate,
                'sync' => $offer,
            ]);
        }
        return $this->redirectToRoute(
            'mws_offer_lookup',
            [ // array_merge($request->query->all(), [
                "viewTemplate" => $viewTemplate,
                "page" => 1,
            ], //),
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route(
        '/delete-all/{viewTemplate<[^/]*>?}',
        name: 'mws_offer_delete_all',
        methods: ['POST'],
    )]
    public function deleteAll(
        string|null $viewTemplate,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) { // TODO : only for admin too ?
            $this->logger->debug("Fail auth with", [$request]);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-offer-delete-all', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $qb = $this->em->createQueryBuilder()
            ->delete(MwsOffer::class, 'o');
        $query = $qb->getQuery();
        $query->execute();
        $this->em->flush();

        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return $this->json([
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-delete-all')->getValue(),
                'viewTemplate' => $viewTemplate,
            ]);
        }
        return $this->redirectToRoute(
            'mws_offer_lookup',
            [ // array_merge($request->query->all(), [
                "viewTemplate" => $viewTemplate,
                "page" => 1,
            ], //),
            Response::HTTP_SEE_OTHER
        );
    }

    // Tags are status AND category of status (a category is also a status...)
    #[Route('/tags/list/{viewTemplate<[^/]*>?}', name: 'mws_offer_tags')]
    public function tags(
        $viewTemplate,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
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
                $searchTags = $surveyAnswers['searchTags'] ?? [];
                $searchTagsToAvoid = $surveyAnswers['searchTagsToAvoid'] ?? [];

                return $this->redirectToRoute(
                    'mws_offer_tags',
                    array_merge($request->query->all(), [
                        "viewTemplate" => $viewTemplate,
                        "keyword" => $keyword,
                        "searchTags" => $searchTags,
                        "searchTagsToAvoid" => $searchTagsToAvoid,
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
        $offerTagsByCatSlugAndSlug = $mwsOfferStatusRepository->findAll();
        $offerTagsByCatSlugAndSlug = array_combine(array_map(function ($t) {
            return $t->getSlug();
        }, $offerTagsByCatSlugAndSlug), $offerTagsByCatSlugAndSlug);

        return $this->render('@MoonManager/mws_offer/tags.html.twig', [
            'offerTagsByCatSlugAndSlug' => $offerTagsByCatSlugAndSlug,
            'tags' => $tags,
            'filtersForm' => $filtersForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/tags/reset-to-default/{viewTemplate<[^/]*>?}', name: 'mws_offer_tags_reset_to_default')]
    public function tagsResetToDefault(
        $viewTemplate,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        // TODO : sync default tags FROM config files instead of hard coded data ?
        // $defaultTags = $this->getParameter('mws_moon_manager.offer.defaultTags') ?? [];
        $moonManagerParams = $this->getParameter('mws_moon_manager') ?? [];
        $offerParams = $moonManagerParams['offer'];
        $defaultTags = $offerParams['defaultTags'];
        // dd($defaultTags);
        if ($request->get('cleanAllTags', false)) {
            $qb = $this->em->createQueryBuilder()
            ->delete(MwsOfferStatus::class, 's');
            $qb->getQuery()->execute();
        }

        foreach ($defaultTags as $tag) {
            // TODO : user serializer format instead of custom extract ?
            $sourceStatusLabel = $tag['label'] ?? null;
            $sourceCategorySlug = $tag['categorySlug'] ?? null;

            $sourceStatusSlug = $sourceStatusLabel ? strtolower($this->slugger->slug($sourceStatusLabel)) : null;
            if ($sourceCategorySlug) {
                $sourceCategory = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
                    $sourceCategorySlug,
                    null
                );
                if (!$sourceCategory) {
                    $sourceCategory = new MwsOfferStatus();
                    $sourceCategory->setSlug($sourceCategorySlug);
                    $sourceCategory->setLabel($sourceCategorySlug);
                    $this->em->persist($sourceCategory);
                }
            }
            $sourceTag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
                $sourceStatusSlug,
                $sourceCategorySlug
            );
            if (!$sourceTag) {
                $sourceTag = new MwsOfferStatus();
            }
            $sourceTag->setSlug($sourceStatusSlug);
            $sourceTag->setLabel($sourceStatusLabel);
            $sourceTag->setCategorySlug($sourceCategorySlug);
            $this->em->persist($sourceTag);
            $this->em->flush();
        }

        return $this->redirectToRoute(
            'mws_offer_tags',
            array_merge($request->query->all(), [
                "viewTemplate" => $viewTemplate,
                "page" => 1,
            ]),
            Response::HTTP_SEE_OTHER
        );
    }



    #[Route(
        '/tag/edit/{categorySlug<[^/]*>}/{slug}/{viewTemplate<[^/]*>?}',
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
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }
        $tag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
            $slug,
            $categorySlug,
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

        $offerTagsByCatSlugAndSlug = $mwsOfferStatusRepository->findAll();
        $offerTagsByCatSlugAndSlug = array_combine(array_map(function ($t) {
            return $t->getSlug();
        }, $offerTagsByCatSlugAndSlug), $offerTagsByCatSlugAndSlug);

        return $this->render('@MoonManager/mws_offer/tagEdit.html.twig', [
            'tag' => $tag,
            'form' => $form,
            // TODO : remove code duplication, inject as global for 
            // all routes in this controller routes renderings with offerTagsByCatSlugAndSlug
            // AND all other redux indexes to send JS frontend side...
            'offerTagsByCatSlugAndSlug' => $offerTagsByCatSlugAndSlug,
            'viewTemplate' => $viewTemplate,
            'title' => "Modifier le tag {$tag->getLabel()}"
        ]);
    }

    #[Route(
        '/tag/delete/{viewTemplate<[^/]*>?}',
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
    ): Response {
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
            $tagSlug,
            $tagCategorySlug
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

    #[Route(
        '/tag/add/{viewTemplate<[^/]*>?}',
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
    ): Response {
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
            $tagSlug,
            $tagCategorySlug
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
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-tag-add')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    // #[Route('/fetch-root-url', name: 'mws_offer_fetchRootUrl')]
    // public function fetchRootUrl(
    //     Request $request,
    // ): Response {
    //     $user = $this->getUser();
    //     if (!$user) {
    //         throw $this->createAccessDeniedException('Only for logged users');
    //     }
    //     $url = $request->query->get('url', null);
    //     $this->logger->debug("Will fetch url : $url");
    //     // Or use : https://symfony.com/doc/current/http_client.html
    //     $respData = file_get_contents($url); // TODO : secu issue, norrow to domain usage ONLY, disallow read of sensible files...
    //     $response = new Response($respData);
    //     // TIPS : too late after pdf echo...
    //     $response->headers->set('Content-Type', 'application/pdf');
    //     $response->headers->set('Cache-Control', 'no-cache');
    //     $response->headers->set('Pragma', 'no-chache');
    //     $response->headers->set('Expires', '0');
    //     return $response;
    // }

    #[Route(
        '/export/{viewTemplate<[^/]*>?}',
        name: 'mws_offer_export',
        methods: ['POST', 'GET'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function export(
        string|null $viewTemplate,
        Request $request,
        MwsOfferRepository $mwsOfferRepository,
        // UploaderHelper $uploaderHelper,
        // CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth", []);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        // TIPS : no csrf renew ? only check user logged is ok ?
        // TODO : or add async CSRF token manager notif route
        //         to sync new redux token to frontend ?
        // $csrf = $request->request->get('_csrf_token');
        // if (!$this->isCsrfTokenValid('mws-csrf-timing-export', $csrf)) {
        //     $this->logger->debug("Fail csrf with", [$csrf]);
        //     throw $this->createAccessDeniedException('CSRF Expired');
        // }

        $format = $request->get('format') ?? 'yaml';
        $offerLookup = $request->get('offerLookup');
        // $attachThumbnails = $request->get('attachThumbnails');

        $qb = $mwsOfferRepository->createQueryBuilder('o');

        if ($offerLookup) {
            $offerLookup = json_decode($offerLookup, true);
            $mwsOfferRepository->applyOfferLokup($qb, $offerLookup);
        }

        $tSlots = $qb->getQuery()->getResult();
        $em = $this->em;
        $self = $this;

        // TODO : bulk process instead of quick hack :
        $nbRefreshPerBulks = 5;
        $slotsCount = count($tSlots);
        $flushTriggerMaxCount = $slotsCount / $nbRefreshPerBulks;
        $flushForseen = $flushTriggerMaxCount;
        $progressLogMax = 500;
        $progressCount = 0;

        $tryFlush = function () use (
            &$flushForseen,
            $flushTriggerMaxCount,
            $em,
            &$progressCount,
            $progressLogMax,
            $self,
            $slotsCount,
        ) {
            $progressCount++;
            if (0 === ($progressCount % $progressLogMax)) {
                $self->logger->warning(
                    "MwsOfferController did process "
                        . "$progressCount slots /  $slotsCount"
                );
            }
            if ($flushForseen <= 1) {
                $flushForseen = $flushTriggerMaxCount;
                $em->flush();
                // dump($flushForseen);
            }
            $flushForseen--;
        };
        // $tmpDir = $this->createTmpDir();

        $tagsSerialized = $this->serializer->serialize(
            $tSlots,
            $format,
            // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            [
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    // ...($attachThumbnails ? [] : ['thumbnailJpeg']),
                    'id'
                ],
                // ObjectNormalizer::IGNORED_ATTRIBUTES => ['tags']
                AbstractNormalizer::CALLBACKS => [
                    'tags' => function ($objects) {
                        if (is_string($objects)) {
                            // Denormalize (cf timing import, not used by export)
                            throw new Exception("Should not happen for : " . $objects);
                        } else {
                            // Normalise
                            $norm = array_map(
                                function (MwsOfferStatus $o) {
                                    // return $o->getSlug();
                                    return [
                                        'slug'  => $o?->getSlug(),
                                        'categorySlug'  => $o?->getcategorySlug(),
                                    ] ?? null;
                                },
                                $objects->toArray() ?? []
                            );
                            sort($norm);
                            return $norm;
                        }
                    },
                    'timingTags' => function ($objects) {
                        if (is_string($objects)) {
                            // Denormalize (cf timing import, not used by export)
                            throw new Exception("Should not happen for : " . $objects);
                        } else {
                            // Normalise
                            $norm = array_map(
                                function (MwsTimeTag $t) {
                                    // return $o->getSlug();
                                    return [
                                        'slug'  => $t?->getSlug(),
                                    ] ?? null;
                                },
                                $objects->toArray() ?? []
                            );
                            sort($norm);
                            return $norm;
                        }
                    },
                    'mwsOfferTrackings' => function ($objects) {
                        if (is_string($objects)) {
                            // Denormalize (cf timing import, not used by export)
                            throw new Exception("Should not happen for : " . $objects);
                        } else {
                            // Normalise
                            $norm = array_map(
                                function (MwsOfferTracking $t) {
                                    // return $o->getSlug();
                                    // return $t;

                                    return [
                                        // TODO : discrimintant to link offer => slug + cat slug ? need query if so...
                                        // 'offer'  => [
                                        //     // tips : username from Class constant ? used by getUserIdentifier in our case...
                                        //     'id' => $t?->getOffer()?->getId(),
                                        //     'slug' => $t?->getOffer()?->getSlug(),
                                        // ],
                                        // 'owner'  => $t?->getOwner()?->getUserIdentifier(),
                                        'owner'  => [
                                            // TODO : username from Class constant ? used by getUserIdentifier in our case...
                                            'username' =>
                                            $t?->getOwner()?->getUserIdentifier()
                                        ],
                                        'offerStatusSlug'  => $t?->getOfferStatusSlug(),
                                        'comment' => $t?->getComment(),
                                        'updatedAt' => $t?->getUpdatedAt(),
                                        'createdAt' => $t?->getCreatedAt(),
                                    ] ?? null;
                                },
                                $objects->toArray() ?? []
                            );
                            // sort($norm);
                            return $norm;
                        }
                    },
                ]
            ],
        );
        // dd('ok');
        $em->flush();

        $rootPackage = \Composer\InstalledVersions::getRootPackage();
        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];

        $filename = "MoonManager-v" . $packageVersion
            . "-OffersExport-" . time() . ".{$format}"; // . '.pdf';

        $response = new Response();

        //set headers
        $mime = [
            'json' => 'application/json',
            'csv' => 'text/comma-separated-values',
            'xml' => 'application/x-xml', // TODO : x-xml or xml ?
            'yaml' => 'application/x-yaml', // TODO : x-yaml or yaml ?
        ][$format] ?? 'text/plain';
        if ($mime) {
            $response->headers->set('Content-Type', $mime);
        }
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');

        $response->setContent($tagsSerialized);
        return $response;
    }

    #[Route(
        '/import/{viewTemplate<[^/]*>?}',
        name: 'mws_offer_import',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function import(
        string|null $viewTemplate,
        Request $request,
        MwsOfferRepository $mwsOfferRepository,
        MwsOfferTrackingRepository $mwsOfferTrackingRepository,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsContactRepository $mwsContactRepository,
        MwsUserRepository $mwsUserRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
        SluggerInterface $slugger,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth", []);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        // dd($csrf);
        if (!$this->isCsrfTokenValid('mws-csrf-offer-import', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $maxTime = 60 * 600; // 600 minutes max // TODO : from crm config and/or user config ?
        set_time_limit($maxTime);
        ini_set('max_execution_time', $maxTime);

        $format = $request->get('format', 'monwoo-extractor-export');
        $shouldOverwrite = $request->get('shouldOverwrite');
        $importFile = $request->files->get('importFile');
        // $importContent = $importFile ? file_get_contents($importFile->getPathname()) : '[]';
        $importReport = '';

        $forceCurrentStatusSlugRewrite = $request->query->get('forceCurrentStatusSlugRewrite', false);
        $forceCleanTags = $request->query->get('forceCleanTags', false);
        $forceCleanContacts = $request->query->get('forceCleanContacts', false);

        if ($importFile) {
            $originalFilename = $importFile->getClientOriginalName();
            // TIPS : $importFile->guessExtension() is based on mime type and may fail
            // to be .yaml if text detected... (will be .txt instead of .yaml...)
            // $extension = $importFile->guessExtension();
            $extension = array_slice(explode(".", $originalFilename), -1)[0];
            $originalName = implode(".", array_slice(explode(".", $originalFilename), 0, -1));
            // this is needed to safely include the file name as part of the URL
            $safeFilename = $slugger->slug($originalName);

            $newFilename = $safeFilename . '_' . uniqid() . '.' . $extension;

            $importContent = file_get_contents($importFile->getPathname());
            // https://www.php.net/manual/fr/function.iconv.php
            // https://www.php.net/manual/en/function.mb-detect-encoding.php
            // $importContent = iconv("ISO-8859-1", "UTF-8", $importContent);
            $importContentEncoding = mb_detect_encoding($importContent);
            // dd($importContentEncoding);
            $importContent = iconv($importContentEncoding, "UTF-8", $importContent);
            // dd($importContent);
            // TIPS : clean as soon as we can...
            unlink($importFile->getPathname());
            // $importReport = $importContent;
            /** @var MwsOffer[] */
            $offersDeserialized = $this->deserializeOffers(
                $user,
                $importContent,
                $format,
                $newFilename,
                $mwsOfferRepository,
                $mwsOfferStatusRepository,
                $mwsOfferTrackingRepository,
                $mwsContactRepository,
                $mwsUserRepository,
                $mwsTimeTagRepository,
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
                    ->andWhere('o.sourceName = :sourceName OR o.sourceName IS NULL')
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
                    if ($shouldOverwrite) {
                        $importReport .= "<strong>Surcharge le doublon : </strong> [$sourceName , $slug]<br/>";
                        $offerInput = $offer;
                        // $offer = $allDuplicates[0];
                        /** @var MwsOffer $offer */
                        $offer = array_shift($allDuplicates);
                        // $sync = function ($path) use ($inputOffer, $offer) {
                        //     $set = 'set' . ucfirst($path);
                        //     $get = 'get' . ucfirst($path);
                        //     $v =  $inputOffer->$get();
                        //     if (
                        //         null !== $v &&
                        //         ((!is_string($v)) || strlen($v))
                        //     ) {
                        //         $offer->$set($v);
                        //     }
                        // };
                        $sync = function ($path, $setter = null) use (&$offer, &$offerInput) {
                            $get = 'get' . ucfirst($path);
                            // $v =  $offerInput[$path] ?? null;
                            $v =  $offerInput->$get();
                            if (null !== $v) {
                                $set = 'set' . ucfirst($path);
                                if (!method_exists($offer, $set)) {
                                    // Is collection :
                                    $add = 'add' . ucfirst($path);
                                    if (!method_exists($offer, $add)) {
                                        $add = preg_replace('/s$/', '', $add);
                                    }
                                    $collection = $offer->$get();
                                    $collection->clear();
                                    foreach ($v as $subV) {
                                        if ($setter) {
                                            $setter($offer, $subV);
                                        } else {
                                            $offer->$add($subV);
                                        }
                                    }
                                } else {
                                    if ($setter) {
                                        $setter($offer, $v);
                                    } else {
                                        $offer->$set($v);
                                    }
                                }
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
                        if ($forceCleanTags) {
                            $offer->getTags()->clear();
                        }
                        if ($forceCurrentStatusSlugRewrite) {
                            $sync('currentStatusSlug');
                            $slugs = explode('|', $offer->getCurrentStatusSlug());
                            $currentStatusTag = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
                                $slugs[1],
                                $slugs[0]
                            );
                            // TODO : if currentStatusTag not found ? wrong slug ? etc ...
                            // TODO : refactor to : slug / nameSlug / categorySlug
                            // since slug is now used for name and full slug key...

                            // TIPS : add to ensure tag list ok without duplicata :
                            // $offer->addTag($currentStatusTag); // WARNING : refactor or warn ? this one to not check category exclusivity etc...
                            // $offer->addTag($currentStatusTag);
                            $mwsOfferRepository->addTag($offer, $currentStatusTag);
                        }

                        // $tags = $inputOffer->getTags();
                        $tags = $offerInput->getTags();
                        foreach ($tags as $tag) {
                            // TIPS : inside addTag, only one by specific only one choice category type ?
                            // OK to copy all, clone src use
                            // $mwsOfferRepository->addTag($offer, $tag);
                            // $offer->addTag($tag); // TODO : clean up script in case someone use the add without category dups check ?
                            // But still safer to use repo algo :
                            $mwsOfferRepository->addTag($offer, $tag);
                        }
                        // $sync('mwsOfferTrackings');

                        if ($forceCleanContacts) {
                            $offer->getContacts()->clear();
                        }
                        $contacts = $offerInput->getContacts();
                        foreach ($contacts as $contact) {
                            $offer->addContact($contact);
                            // dd($contact);
                            // Try to fill offer quick contact with contact details if available :
                            // if ($contact->getPhone()) {
                            //     if (!$offer->getContact1()) {
                            //         $offer->setContact1($contact->getPhone());
                            //     } else if (!$offer->getContact2()) {
                            //         $offer->setContact2($contact->getPhone());
                            //     }
                            // }
                            // if ($contact->getEmail()) {
                            //     if (!$offer->getContact1()) {
                            //         $offer->setContact1($contact->getEmail());
                            //     } else if (!$offer->getContact2()) {
                            //         $offer->setContact2($contact->getEmail());
                            //     }
                            // }
                        }

                        // dump($inputOffer);
                        // dd($offer);
                        // CLEAN all possible other duplicates :
                        foreach ($allDuplicates as $otherDups) {
                            $em->remove($otherDups);
                        }
                        // TODO : add comment to some tracking entities, column 'Observations...' or too huge for nothing ?

                        // if (!$offer->getSourceFile()
                        // || !strlen($offer->getSourceFile())) {
                        //     $offer->setSourceFile('unknown');
                        // }

                        // Default auto compute values :
                        /** @var MwsOffer $offer */
                        if (!$offer->getCurrentStatusSlug()) {
                            // TODO : from .env config file ? or from DB ? (status with tags for default selections ?)
                            $offerStatusSlug = "mws-offer-tags-category-src-import|a-relancer"; // TODO : load from status DB or config DB...
                            $offer->setCurrentStatusSlug($offerStatusSlug);
                            // dd($offer);    
                        }
                    } else {
                        $importReport .= "<strong>Ignore le doublon : </strong> [$sourceName,  $slug]<br/>";
                        continue; // TODO : WHY BELOW counting one write when all is duplicated ?
                    }
                }
                // Try to fill offer quick contact with contact details if available :
                $contacts = $offer->getContacts();
                $isWaitingForData = function($test) {
                    return [
                        `Voir l'adresse email` => true,
                        `Voir le téléphone` => true,
                    ][$test] ?? !$test;
                }; // TODO (if isWaitingForData in phone and phone comme with null or empty ? => overwritte, was empty phone data stuff...)
                foreach ($contacts as $contact) {
                    // TIPS : ensure no phone duplication not working... import duplicate phone if offer already define phone... ?
                    //        check cache issue... codeur-com-420626, JANTIER recherche des développeurs et gestionnaires E-commerc
                    if (
                        $contact->getPhone() !== $offer->getContact1()
                        && $contact->getPhone() !== $offer->getContact2()
                    ) {
                        if ($isWaitingForData($offer->getContact1())) {
                            $offer->setContact1($contact->getPhone());
                        } else if ($isWaitingForData($offer->getContact2())) {
                            $offer->setContact2($contact->getPhone());
                        }
                    }
                    if (
                        $contact->getEmail() !== $offer->getContact1()
                        && $contact->getEmail() !== $offer->getContact2()
                    ) {
                        if ($isWaitingForData($offer->getContact1())) {
                            $offer->setContact1($contact->getEmail());
                        } else if ($isWaitingForData($offer->getContact2())) {
                            $offer->setContact2($contact->getEmail());
                        }
                    }
                }
                // TODO : clean tag option before import
                $tTags = $offer->getTimingTags();
                foreach ($tTags as $tTag) {
                    // $tTag->removeMwsOffer($offer); // TO hard to know non percisetent, delete before add instead...
                    $tTag->addMwsOffer($offer);
                }

                $trackings = $offer->getMwsOfferTrackings();
                // dd($trackings);
                // $trackings = $offerInput->getMwsOfferTrackings();
                // $offer->getMwsOfferTrackings()->clear();
                foreach ($trackings as $tracking) {
                    if ($tracking->getOffer()->getId()
                    !== $offer->getId()) {
                        dd($tracking);
                    }
                    $tracking->setOffer($offer);
                    // $offer->addMwsOfferTracking($tracking);
                    // $em->persist($tracking);
                }
                // TIPS : below avoided by sending offer id in export files, even if guessable from parent, desizeializer might not know id Yet
                // $validTrackings = []; // TODO : cf extract, offers check not done, done below:
                // foreach ($trackings as $tracking) {
                //     // dump($tracking);
                //     if ($tracking->getOffer()->getId()
                //     != $offer->getId()) {
                //         // TODO : strange : below not called ? offer id already ok ? buggy from somewhere, need code clean up to track bug...
                //         // dump($offer->getId());
                //         // dd($tracking->getOffer()->getId());
                //             // DID mismatch lookup, should have instanciate new obj :
                //         // TODO : clone without cloning ID ? or reflexion to force remove ID to create new entity is enough ?
                //         $t = new MwsOfferTracking();
                //         $t->setOwner($tracking->getOwner());
                //         $t->setOfferStatusSlug($tracking->getOfferStatusSlug());
                //         $t->setComment($tracking->getComment());
                //         $t->setCreatedAt($tracking->getCreatedAt());
                //         $tracking = $t;
                //     }
                //     $tracking->setOffer($offer);
                //     $validTrackings[] = $tracking;
                // }
                // // dd($offer);
                // $offer->getMwsOfferTrackings()->clear();
                // foreach ($validTrackings as $t) {
                //     $offer->addMwsOfferTracking($t);
                // }

                // TODO : multiple SAME trakings is BUGGy, cf test case
                
                // $offer->getMwsOfferTrackings()->clear();
                // $offer->getContacts()->clear();
                // dd($offer);
                // if ($offer->getSlug() !== 'source-test-localhost-336397') {
                //     dd($offer);
                // }
                // if ($offer->getSlug() == 'source-test-localhost-404632') {
                //     dd($offer);
                // }

                $em->persist($offer);
                $em->flush();
                $savedCount++;
            }
            $importReport .= "<br/><br/>Enregistrement de $savedCount offres OK <br/>";

            // var_dump($extension);var_dump($importContent);var_dump($offersDeserialized); exit;
        } else {
            $importReport .= "<strong>Missing import file.</strong><br/>";
        }

        return $this->json([
            'importReport' => $importReport,
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-import')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    // TODO : inside some Services helper class instead ?
    public function offerSlugToSourceUrlTransformer($sourceName)
    {
        return [ // TODO : from configs or services ?
            // Specific url rewriters per source ID :
            'source.test.localhost' => function ($oSlug) {
                return "http://source.test.localhost/offers/$oSlug";
            },
        ][$sourceName] ?? function ($oSlug) use ($sourceName) {
            return "https://$sourceName/projects/$oSlug";
        };
    }
    public function usernameToClientUrlTransformer($sourceName)
    {
        return [ // TODO : from configs or services ?
            'source.test.localhost' => function ($username) {
                return "http://source.test.localhost/-$username";
            },
        ][$sourceName] ?? function ($oSlug) use ($sourceName) {
            return "https://$sourceName/-$oSlug";
        };
    }

    // TODO : more like 'loadOffers' than deserialize,
    //        will save in db too for code factorisation purpose...
    public function deserializeOffers(
        MwsUser $user,
        String $data,
        String $format,
        String $sourceFile,
        MwsOfferRepository $mwsOfferRepository,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
        MwsOfferTrackingRepository $mwsOfferTrackingRepository,
        MwsContactRepository $mwsContactRepository,
        MwsUserRepository $mwsUserRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
    ) {
        /** @param MwsOffer[] **/
        $out = null;
        // dd($format);
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
                    $offer = new MwsOffer();

                    $cleanUp = function ($val) {
                        // NO-BREAK SPACE (U+A0)
                        // https://stackoverflow.com/questions/150033/regular-expression-to-match-non-ascii-characters
                        // https://stackoverflow.com/questions/55904971/regex-in-php-returning-preg-match-compilation-failed-pcre-does-not-support
                        // https://stackoverflow.com/questions/40724543/how-to-replace-decoded-non-breakable-space-nbsp/40724830#40724830
                        // return $val;
                        // return trim(preg_replace('/[\s\xc2\xa0]+/', ' ', $val));
                        return trim(
                            // NO-BREAK SPACE (U+A0)
                            preg_replace(
                                '/(\xc2\xa0)+/',
                                ' ',
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
                        $sourceStatusSlug,
                        $sourceCategorySlug
                    );
                    // dd($sourceTag);
                    if (!$sourceTag) {
                        $sourceCategory = $mwsOfferStatusRepository->findOneWithSlugAndCategory(
                            $sourceCategorySlug,
                            null
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
                    $offerStatusSlug = "$sourceCategorySlug|$sourceStatusSlug"; // TODO : load from status DB or config DB...

                    // TODO : tips and doc, OR refactor : MUST be called before
                    // ->addTag since will disallow removal of last status if done after
                    // indeed, removal of tag used as currentStatus is forbidden
                    $offer->setCurrentStatusSlug($offerStatusSlug);
                    $mwsOfferRepository->addTag($offer, $sourceTag);

                    $offer->setLeadStart($leadStart);
                    // $offer->setContact3($o["..."]);
                    $offer->setSourceUrl(
                        $this->offerSlugToSourceUrlTransformer($sourceSlug)($offerSlug)
                    );
                    $offer->setClientUrl($contactBusinessUrl);
                    // $offer->setTitle($o['title']);
                    $offer->setSourceName($sourceSlug);
                    $offer->setSourceDetail($o);

                    // TODO : getClientSlug that ensure contact unicity ?

                    $c = $contactIndex[$userId] ?? [];
                    $contact = $mwsContactRepository->findOneWithIdAndEmail(
                        $userId,
                        $c['email'] ?? null
                    );
                    if (null === $contact) {
                        $contact = new MwsContact();
                    }

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
                    $tracking = new MwsOfferTracking();
                    $tracking->setOffer($offer);
                    $tracking->setOwner($user);
                    $tracking->setComment("Imported from : $sourceFile");
                    $tracking->setOfferStatusSlug($offerStatusSlug);
                    $offer->addMwsOfferTracking($tracking);

                    $out[] = $offer;
                }
            }
        } else {
            // $out = $this->serializer->deserialize(
            //     $data,
            //     MwsOffer::class . "[]",
            //     $format,
            //     // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            // );

            $em = $this->em;
            $self = $this;
            // dd("ok");
            /** @var MwsTimeSlot[] $importSlots */
            $out = $this->serializer->deserialize(
                $data,
                MwsOffer::class . "[]",
                $format,
                [
                    // TODO : transform class load instead of type ignore ?
                    ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                    AbstractNormalizer::CALLBACKS => [
                        'tags' => function (
                            $innerObject,
                            $outerObject,
                            string $attributeName,
                            string $format = null,
                            array $context = []
                        ) use ($mwsOfferStatusRepository, &$importReport, $em) {
                            // dump($context['deserialization_path']);
                            // if (is_array($innerObject)) {
                            if ($context['deserialization_path'] ?? null) {
                                // dd($innerObject); // TODO ; can't have raw input string ?
                                // throw new Exception("TODO : ");
                                return array_filter(
                                    array_map(function ($tagSlug)
                                    use ($mwsOfferStatusRepository, &$importReport, &$context, $em) {
                                        $tag = $mwsOfferStatusRepository->findOneBy([
                                            'slug' => $tagSlug->getSlug(),
                                            'categorySlug' => $tagSlug->getCategorySlug(),
                                        ]);
                                        // dd($tag);
                                        // TODO ; if null tag ?
                                        if (!$tag) {
                                            if ($tagSlug->getSlug() && strlen($tagSlug->getSlug())) {
                                                $importReport .= "Missing tag for slug {$tagSlug->getSlug()} for {$context['deserialization_path']} <br/>";

                                                // TODO : Adding missing tags ? nop ? too hard to guess with slug only ? cat slug etc ?
                                                // $pendingNewTags[$tagSlug->getSlug()] = true;
                                                // $tag = new MwsTimeTag();
                                                // $tag->setSlug($tagSlug->getSlug());
                                                // $tag->setLabel("#{$tagSlug->getSlug()}#");
                                                // // TIPS : even if will be saved with 'cascade persiste' attribute
                                                // // save it now to see it on others imports lookups instead of at
                                                // // end of full import query builds...
                                                // $em->persist($tag);
                                                // $em->flush();
                                            } else {
                                                // TIPS : no need to warn, for CSV emoty row will be null...
                                                // $importReport .= "WARNING : null tag for {$context['deserialization_path']} <br/>";
                                                // $this->logger->warning("WARNING : null tag for {$context['deserialization_path']}");
                                            }
                                        }
                                        return $tag;
                                    }, $innerObject),
                                    function ($t) {
                                        // filter null
                                        return !!$t;
                                    }
                                );
                            } else {
                                // Normalise (cf timing export, not used by import)
                                throw new Exception("Should not happen");
                            }
                        },
                        'timingTags' => function (
                            $innerObject,
                            $outerObject,
                            string $attributeName,
                            string $format = null,
                            array $context = []
                        ) use ($mwsTimeTagRepository, &$importReport, $em) {
                            // dump($context['deserialization_path']);
                            // if (is_array($innerObject)) {
                            if ($context['deserialization_path'] ?? null) {
                                // dd($innerObject); // TODO ; can't have raw input string ?
                                // throw new Exception("TODO : ");
                                return array_filter(
                                    array_map(function ($timingTagSlug)
                                    use ($mwsTimeTagRepository, &$importReport, &$context, $em) {
                                        $tag = $mwsTimeTagRepository->findOneBy([
                                            'slug' => $timingTagSlug->getSlug(),
                                        ]);
                                        // dd($tag);
                                        // TODO ; if null tag ?
                                        if (!$tag) {
                                            if ($timingTagSlug->getSlug() && strlen($timingTagSlug->getSlug())) {
                                                $importReport .= "Missing timing tag for slug {$timingTagSlug->getSlug()} for {$context['deserialization_path']} <br/>";

                                                // TODO : Adding missing tags ? nop ? too hard to guess with slug only ? cat slug etc ?
                                                // $pendingNewTags[$tagSlug->getSlug()] = true;
                                                // $tag = new MwsTimeTag();
                                                // $tag->setSlug($tagSlug->getSlug());
                                                // $tag->setLabel("#{$tagSlug->getSlug()}#");
                                                // // TIPS : even if will be saved with 'cascade persiste' attribute
                                                // // save it now to see it on others imports lookups instead of at
                                                // // end of full import query builds...
                                                // $em->persist($tag);
                                                // $em->flush();
                                            } else {
                                                // TIPS : no need to warn, for CSV emoty row will be null...
                                                // $importReport .= "WARNING : null tag for {$context['deserialization_path']} <br/>";
                                                // $this->logger->warning("WARNING : null tag for {$context['deserialization_path']}");
                                            }
                                        }
                                        return $tag;
                                    }, $innerObject),
                                    function ($t) {
                                        // filter null
                                        return !!$t;
                                    }
                                );
                            } else {
                                // Normalise (cf timing export, not used by import)
                                throw new Exception("Should not happen");
                            }
                        },
                        'offer' => function (
                            $innerObject,
                            $outerObject,
                            string $attributeName,
                            string $format = null,
                            array $context = []
                        ) use ($mwsOfferTrackingRepository, $mwsUserRepository, &$importReport, $em) {
                            // dd($innerObject); // TIPS : MUST not be blacklisted from attribute to work... 
                            // TODO : can't blacklist in model and wight list back on serializer usage ? Need custom Rev-Ing or re-codes ?
                            if ($context['deserialization_path'] ?? null) {
                                // dd($innerObject); // TODO ; can't have raw input string ?
                                // throw new Exception("TODO : ");
                                return [ 'slug' => $innerObject->getSlug() ];
                            } else {
                                // Normalise (cf timing export, not used by import)
                                throw new Exception("Should not happen");
                            }
                        },  
                        'mwsOfferTrackings' => function (
                            $innerObject,
                            $outerObject,
                            string $attributeName,
                            string $format = null,
                            array $context = []
                        ) use ($mwsOfferTrackingRepository, $mwsOfferRepository, $mwsUserRepository, &$importReport, $em) {
                            // dump($context['deserialization_path']);
                            // if (is_array($innerObject)) {
                            // dd($outerObject);
                            if ($context['deserialization_path'] ?? null) {
                                // dd($innerObject); // TODO ; can't have raw input string ?
                                // throw new Exception("TODO : ");
                                // return [];
                                // dd($innerObject);
                                // dd($outerObject);
                                return array_filter(
                                    array_map(function ($trackingInput)
                                    use ($mwsOfferTrackingRepository, $mwsOfferRepository, $mwsUserRepository, &$importReport, &$context, $em) {
                                        // dd($trackingInput);
                                        $criteria = [
                                            // 'owner.username' => $trackingInput->getOwner()->getUsername(),
                                            'offerStatusSlug' => $trackingInput->getOfferStatusSlug(),
                                            'comment' => $trackingInput->getComment(),
                                            'createdAt' => $trackingInput->getCreatedAt(),
                                            // TODO : don't know yet linked offer..., cf next line, will clone if not tracking from offer ?
                                            // 'offer' => [ 'slug' => $trackingInput->getOffer()->getSlug() ],
                                        ];

                                        // if ($trackingInput->getOffer()) {
                                        //     // TODO : why null even if Slug defined in input src ? ok for Owner but not for Offer ? why ?
                                        //     $criteria['offer'] = [
                                        //         'slug' => $trackingInput->getOffer()->getSlug(),
                                        //     ];
                                        // }
                                        // $offer = $mwsOfferRepository->findOneBy([
                                        //     'slug' => $trackingInput->getOffer()->getSlug()
                                        // ]);
                                        // dd($offer);

                                        // TODO : using $tracking from repository let NotSaved Entity issue... 
                                        // Try without tracking null to see buggy imort to solve :
                                        // $tracking = $mwsOfferTrackingRepository->findOneBy($criteria);
                                        // dd($trackingInput);
                                        // dd($tag);
                                        // TODO ; if null tag ?
                                        $tracking = null;
                                        if (!$tracking) {
                                            $tracking = new MwsOfferTracking();
                                        }
                                        // $tracking = new MwsOfferTracking();
                                        // dd($trackingInput->getOwner());
                                        $uName = $trackingInput->getOwner()->getUserIdentifier();
                                        $u = $mwsUserRepository->findOneBy([
                                            "username" => $uName, // TODO : 'username' from class const userIdentifierKey stuff ? but for user identifier as computed values, will not work... => custom Query builder or crieteria or ???
                                        ]) ?? null; // TODO : no user or user importing stuff as replacement or re-create new user on email behalf ?
                                        // dd($u);
                                        $tracking->setOwner($u);
                                        // $tracking->setOffer($offer);
                                        $tracking->setOfferStatusSlug($trackingInput->getOfferStatusSlug());
                                        $tracking->setComment($trackingInput->getComment());
                                        $tracking->setCreatedAt($trackingInput->getCreatedAt());
                                        // $importReport .= "Missing tag for slug {$trackingInput->getSlug()} for {$context['deserialization_path']} <br/>";

                                        // TIPS : DO NOT persist below since missing offer link, will be done later and 'cascade:persist' will to save of trackings from offer persist...
                                        // $em->persist($tracking);
                                        // $em->flush();
                                        return $tracking;
                                    }, $innerObject),
                                    function ($t) {
                                        // filter null
                                        return !!$t;
                                    }
                                );
                            } else {
                                // Normalise (cf timing export, not used by import)
                                throw new Exception("Should not happen");
                            }
                        },
                        'contacts' => function (
                            $innerObject,
                            $outerObject,
                            string $attributeName,
                            string $format = null,
                            array $context = []
                        ) use ($mwsContactRepository) {
                            // dump($context['deserialization_path']);
                            // if (is_array($innerObject)) {
                            if ($context['deserialization_path'] ?? null) {
                                // dd($innerObject); // TODO ; can't have raw input string ?
                                // throw new Exception("TODO : ");
                                return array_filter(
                                    array_map(function ($contactInput)
                                    use ($mwsContactRepository) {
                                        $contact = $mwsContactRepository->findOneBy([
                                            'username' => $contactInput->getUserName(),
                                            'sourceName' => $contactInput->getSourceName(),
                                            // TODO : which key to overwrite contacts ? + what if email was missing but import have new info ? => will duplicate => so have contact duplicate or merge algo page ? that will ajust in offers links etc...
                                            'email' => $contactInput->getEmail(),
                                        ]);
                                        // dd($tracking);
                                        // dd($tag);
                                        // TODO ; if null tag ?
                                        if (!$contact) {
                                            $contact = new MwsContact();
                                        }
                                        $cSync = function ($path) use ($contactInput, $contact) {
                                            $set = 'set' . ucfirst($path);
                                            $get = 'get' . ucfirst($path);
                                            $v =  $contactInput->$get();
                                            // if (
                                            //     null !== $v && // TODO : check sync restriction to not reset empty or null inputs as cleaned...
                                            //     ((!is_string($v)) || strlen($v))
                                            // ) {
                                            if (
                                                null !== $v &&
                                                ((!is_string($v)) || strlen($v))
                                            ) {
                                                $contact->$set($v);
                                            }
                                        };
                                        // TODO : generic for ORM props ? or PHP class refelctions ?
                                        $cSync('username');
                                        $cSync('status');
                                        $cSync('postalCode');
                                        $cSync('city');
                                        $cSync('avatarUrl');
                                        $cSync('email');
                                        $cSync('phone');
                                        $cSync('sourceDetail');
                                        $cSync('sourceName');
                                        $cSync('businessUrl');
                                        $cSync('createdAt');
                                        // TIPS : DO NOT persist below since missing offer link, will be done later and 'cascade:persist' will to save of trackings from offer persist...
                                        // $em->persist($contact);
                                        // $em->flush();
                                        return $contact;
                                    }, $innerObject),
                                    function ($t) {
                                        // filter null
                                        return !!$t;
                                    }
                                );
                            } else {
                                // Normalise (cf timing export, not used by import)
                                throw new Exception("Should not happen");
                            }
                        },
                    ],
                ]
            );
        }
        // dd("ok");

        return $out;
    }

    /** @param MwsOffer[] $offers */ // TODO : not used, use or keep above code...
    public function serializeOffers($offers, $format)
    {
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
