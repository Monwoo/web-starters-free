<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Entity\MwsContact;
use MWS\MoonManagerBundle\Entity\MwsContactTracking;
use MWS\MoonManagerBundle\Entity\MwsMessage;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;
use MWS\MoonManagerBundle\Entity\MwsOfferTracking;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsOfferImportType;
use MWS\MoonManagerBundle\Form\MwsOfferStatusType;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Form\MwsUserFilterType;
use MWS\MoonManagerBundle\Repository\MwsContactRepository;
use MWS\MoonManagerBundle\Repository\MwsMessageRepository;
use MWS\MoonManagerBundle\Repository\MwsOfferRepository;
use MWS\MoonManagerBundle\Repository\MwsOfferStatusRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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
                        function (MwsOfferStatus $tag) use ($tagSlugSep) {
                            return $tag->getCategorySlug() . $tagSlugSep . $tag->getSlug();
                        },
                        // $mwsOfferStatusRepository->findAll()
                        $mwsOfferStatusRepository
                            ->createQueryBuilder("t")
                            ->where($qb->expr()->isNotNull("t.categorySlug"))
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

        if (count($searchBudgets ?? [])) {
            $orClause = '';
            foreach ($searchBudgets as $idx => $searchBudget) {
                if ($idx) {
                    $orClause .= ' OR ';
                }
                $orClause .= ":budget$idx = o.budget";
                $qb->setParameter("budget$idx", $searchBudget);
            }

            $qb = $qb->andWhere($orClause);
        }

        if ($searchStart && strlen($searchStart)) {
            $searchStart = (new DateTime($searchStart));
            $qb->andWhere("o.leadStart > :searchStart")
                ->setParameter('searchStart', $searchStart);
        }
        if ($searchEnd && strlen($searchEnd)) {
            $searchStart = (new DateTime($searchEnd));
            $qb->andWhere("o.leadStart < :searchEnd")
                ->setParameter('searchEnd', $searchEnd);
        }

        if (count($searchTags) || count($searchTagsToAvoid) || count($searchTagsToInclude)) {
            $qb = $qb
                ->innerJoin('o.tags', 'tag');
        }
        if (count($searchTags)) {
            $orClause = '';
            foreach ($searchTags as $idx => $searchTagSlug) {
                [$category, $slug] = explode($tagSlugSep, $searchTagSlug);
                // $tag = $mwsOfferStatusRepository->findOneBy([
                //     "categorySlug" => $category,
                //     "slug" => $slug,
                // ]);
                if ($idx) {
                    $orClause .= ' OR ';
                }
                // TODO refactor ? CurrentStatusSlug is too hacky stuff ?
                // + TODO : join search on o.tags instead ? 
                // $orClause .= "o.currentStatusSlug = :tag$idx";
                // $orClause .= ":tag$idx in (o.tags)";
                $orClause .= "( :tagSlug$idx = tag.slug";
                $orClause .= " AND :tagCategory$idx = tag.categorySlug )";
                // $qb->setParameter("tag$idx", str_replace($tagSlugSep, '|', $tag));
                // $qb->setParameter("tag$idx", $tag);
                $qb->setParameter("tagSlug$idx", $slug);
                $qb->setParameter("tagCategory$idx", $category);
            }

            $qb = $qb->andWhere($orClause);
        }

        if ($searchTagsToInclude && count($searchTagsToInclude)) {
            // dd($searchTagsToAvoid);
            foreach ($searchTagsToInclude as $idx => $slug) {
                $dql = '';
                [$category, $slug] = explode($tagSlugSep, $slug);
                $tag = $mwsOfferStatusRepository->findOneBy([
                    'slug' => $slug,
                    'categorySlug' => $category,
                ]);

                $dql .= ":tagToInclude$idx MEMBER OF o.tags";
                $qb->setParameter("tagToInclude$idx", $tag);
                // dd($dql);
                $qb = $qb->andWhere($dql);
            }
        }

        if (count($searchTagsToAvoid)) {
            // dd($searchTagsToAvoid);

            foreach ($searchTagsToAvoid as $idx => $searchTagToAvoidSlug) {
                $dql = '';
                [$category, $slug] = explode($tagSlugSep, $searchTagToAvoidSlug);
                $tag = $mwsOfferStatusRepository->findOneBy([
                    'slug' => $slug,
                    'categorySlug' => $category,
                ]);
                // dump($category); dd($slug);
                // $dql .= "NOT ( :tagToAvoidSlug$idx = tag.slug";
                // $dql .= " AND :tagToAvoidCategory$idx = tag.categorySlug )";
                // $dql .= "NOT ( :tagToAvoidSlug$idx MEMBER OF o.tags.slug ";
                // $dql .= " AND :tagToAvoidCategory$idx MEMBER OF o.tags.categorySlug )";
                // $qb->setParameter("tagToAvoidSlug$idx", $slug);
                // $qb->setParameter("tagToAvoidCategory$idx", $category);
                $dql .= ":tagToAvoid$idx NOT MEMBER OF o.tags";
                $qb->setParameter("tagToAvoid$idx", $tag);
                // dd($dql);
                $qb = $qb->andWhere($dql);
            }
        }

        if (count($customFilters)) {
            // $lastWeekDate = new DateTime(getDate(strtotime("-1 week"));
            $lastWeekDate = new DateTime("-1 week");
            $qb = $qb
                ->join('o.contacts', 'contact');
            // dd($customFilters);
            foreach ($customFilters as $customFilter) {
                if ($customFilter === "Ayant une photo") {
                    $qb = $qb->andWhere("contact.avatarUrl IS NOT NULL");
                    $qb = $qb->andWhere("contact.avatarUrl <> ''");
                }
                if ($customFilter === "Moins d'une semaine") {
                    // dd($lastWeekDate);
                    // $qb = $qb->andWhere("CONVERT(DATETIME, o.leadStart) > :startTime")
                    // ->setParameters(['startTime' => $lastWeekDate]);

                    // $qb = $qb->andWhere("o.leadStart > :startTime");
                    // $qb->setParameter('startTime', $lastWeekDate);

                    // ->andWhere('e.date BETWEEN :from AND :to')
                    // ->setParameter('from', $from )
                    // ->setParameter('to', $to)

                    $qb = $qb->andWhere("o.leadStart >= :startTime");
                    $qb->setParameter('startTime', $lastWeekDate);
                }
                if ($customFilter === "Avec un contact") {
                    $qb = $qb->andWhere("
                    o.contact1 IS NOT NULL
                    OR o.contact2 IS NOT NULL
                    ");
                    $qb = $qb->andWhere("
                    o.contact1 <> ''
                    OR o.contact2 <> ''
                    ");
                }
                if ($customFilter === "Sans offre déposée") {
                    // $qb = $qb->andWhere("
                    // o.sourceDetail.monwooOfferId IS NOT NULL
                    // ");
                    // https://dev.mysql.com/doc/refman/5.7/en/json-search-functions.html#function_json-contains
                    // $qb = $qb->andWhere("
                    //  NOT JSON_CONTAINS(o.sourceDetail, '', '$.monwooOfferId')
                    // "); // MySql....

                    // https://github.com/ScientaNL/DoctrineJsonFunctions
                    // JSON_EXTRACT(c.attributes, '$.gender') = :gender
                    $qb = $qb->andWhere("
                     JSON_EXTRACT(o.sourceDetail, '$.monwooOfferId') IS NULL
                    ");
                }
                if ($customFilter === "Avec offre déposée") {
                    $qb = $qb->andWhere("
                     NOT JSON_EXTRACT(o.sourceDetail, '$.monwooOfferId') IS NULL
                    ");
                }
                if ($customFilter === "Est favoris") {
                    $qb = $qb->andWhere("
                     JSON_EXTRACT(o.sourceDetail, '$.isBookmark') = :isBookmark
                    ")
                        ->setParameter('isBookmark', true);
                }
                if ($customFilter === "Manque une réponse") {
                    $autoResponse = 'Nous pouvons commencer par une étude de projet via sessions de 20 minutes en distanciel dès 125,76 € TTC';
                    $msgInfo = '<span class="text-warning">'; // TODO : plateforme msg, not last users msgs
                    $msgOwner = '<span class="font-bold">Moi</span>';
                    // https://stackoverflow.com/questions/744289/how-to-increase-value-by-a-certain-number
                    // https://stackoverflow.com/questions/9987279/possible-to-create-a-calculated-field-in-a-doctrine-query-you-can-then-retrieve
                    // $qb = $qb->select("o, CAST((JSON_LENGTH('$.messages') - 1) as TEXT) as msg_length"); // TODO : should be computed and setup after all filters check, might be more computed values
                    // $qb = $qb->select("o, (JSON_LENGTH('$.messages') - 1) as msgLength"); // TODO : should be computed and setup after all filters check, might be more computed values
                    // https://stackoverflow.com/questions/44404855/how-to-get-last-element-in-a-mysql-json-array
                    // // TIPS : fail with SQLITE, not additions in where clause... ?
                    // nop CONCAT only accept 2 fields...
                    // $qb = $qb->andWhere(" 
                    //  JSON_EXTRACT(o.sourceDetail,
                    //     CONCAT('$.messages[',JSON_LENGTH('$.messages') - 1,']')
                    //  ) LIKE :msgOwner
                    // ")

                    // $qb = $qb->andWhere("
                    //     JSON_EXTRACT(o.sourceDetail,
                    //     " . $qb->expr()->concat(
                    //         "'$.messages['", $qb->expr()->concat(
                    //             // "'0'",// "JSON_LENGTH('$.messages')", // "o.clientUsername", // "'0'",
                    //             $qb->expr()->diff("JSON_LENGTH('$.messages')", 1),
                    //             "']'"
                    //         )
                    //     ) . "
                    //     ) LIKE :msgOwner
                    // ")

                    $qb = $qb->andWhere("
                        (
                            JSON_EXTRACT(o.sourceDetail,
                                MWS_CONCAT(
                                    '$.messages[',
                                    IIF(JSON_LENGTH(o.sourceDetail, '$.messages') > 0,
                                        JSON_LENGTH(o.sourceDetail, '$.messages') - 1,
                                        0
                                    ),
                                    ']'
                                )
                            ) NOT LIKE :msgOwner
                        ) OR (
                            JSON_EXTRACT(o.sourceDetail,
                                MWS_CONCAT(
                                    '$.messages[',
                                    IIF(JSON_LENGTH(o.sourceDetail, '$.messages') > 0,
                                        JSON_LENGTH(o.sourceDetail, '$.messages') - 1,
                                        0
                                    ),
                                    ']'
                                )
                            ) LIKE :autoResponse
                        )
                    ")
                        ->setParameter('msgOwner', '%' . $msgOwner . '%')
                        ->setParameter('autoResponse', '%' . $autoResponse . '%');

                    // https://database.guide/sqlite-json_array_length/
                    // $qb = $qb->andWhere("
                    //     JSON_LENGTH(o.sourceDetail, '$.messages') > 0
                    // ");

                    // dd($qb->getQuery()->getSQL());
                    // dd($qb->expr()->);
                }
                if ($customFilter === "Ordonner par dernier tchat") {
                    // $qb = $qb->andWhere("
                    //  JSON_EXTRACT(o.sourceDetail, '$.messages[-1]') IS TRUE
                    // ");

                    // TODO : pre-compute and/or sanity scripts that remove unused
                    //    uploads / computes values for deep filters as simple SQL query...
                    // project.messages?.slice(-1)[0]
                    //         // TIPS : hand picked way, from currend dataset to extract msd date :
                    //         .split(`class="from-now mb-0" data-bs-original-title="`)[1]
                    //         .split(`"`)[0]
                }
                if ($customFilter === "Ordonner par concurrence minimum") {
                    $qb = $qb->orderBy("
                        JSON_EXTRACT(o.sourceDetail, '$.projectOffers')
                    ");

                    // TODO : Remove other order by tags from KNP bundle ? or combine ?
                    // $qb->addOrderBy($request->query->get('sort'),$request->query->get('direction'));
                    $request->query->remove('sort');
                    $request->query->remove('direction');
                }
                if ($customFilter === "Ordonner par meilleure taux de réponse") {
                    // $qb = $qb->andWhere("
                    //  JSON_EXTRACT(o.sourceDetail, '$.isBookmark') IS TRUE
                    // ");
                    // TODO : pre-compute and/or sanity scripts that remove unused
                    //    uploads / computes values for deep filters as simple SQL query...
                    // projectKeys.sort((k1, k2) => {
                    //     const p1 = sharedDataset.projects[k1];
                    //     const p2 = sharedDataset.projects[k2];
                    //     const p2Status = p2.projectStatus || "Fermé";
                    //     const p1Status = p1.projectStatus || "Fermé";
                    //     return (p2Status.includes("Ouvert") - p1Status.includes("Ouvert")) ||
                    //         ((p2.projectOffersViewed / p2.projectOffers) - (p1.projectOffersViewed / p1.projectOffers));
                    // });


                    // $qb = $qb->select("o, (1.0 * JSON_EXTRACT(o.sourceDetail, '$.projectOffersViewed')
                    // / JSON_EXTRACT(o.sourceDetail, '$.projectOffers')) as clientAnswerRatio");
                    // $qb = $qb->orderBy("
                    //     clientAnswerRatio
                    // ", "DESC");

                    // TIPS :
                    // // Will ERROR :
                    // 1.0 * JSON_EXTRACT(o.sourceDetail, '$.projectOffersViewed')
                    // // Will be int casted, always 0 :
                    // JSON_EXTRACT(o.sourceDetail, '$.projectOffersViewed')
                    // / JSON_EXTRACT(o.sourceDetail, '$.projectOffers') * 1.0

                    $qb = $qb->orderBy("
                        JSON_EXTRACT(o.sourceDetail, '$.projectOffersViewed') * 1.0
                        / JSON_EXTRACT(o.sourceDetail, '$.projectOffers')
                    ", "DESC");

                    // Remove other order by tags from KNP bundle ? or combine ?
                    $request->query->remove('sort');
                    $request->query->remove('direction');
                }
            }
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

        $addMessageConfig = [
            // "jsonResult" => rawurlencode(json_encode([])),
            "jsonResult" => rawurlencode('{}'),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsMessageType.json.twig",
                [
                    "availableTemplates" => $availableTemplates,
                    "availableTemplateNameSlugs" => $availableTemplateNameSlugs,
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


        $addMessageConfig = [
            // "jsonResult" => rawurlencode(json_encode([])),
            "jsonResult" => rawurlencode('{}'),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsMessageType.json.twig",
                [
                    "availableTemplates" => $availableTemplates,
                    "availableTemplateNameSlugs" => $availableTemplateNameSlugs,
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

        $offerTagsByCatSlugAndSlug = $mwsOfferStatusRepository->getTagsByCategorySlugAndSlug();
        return $this->render('@MoonManager/mws_offer/view.html.twig', [
            'offerTagsByCatSlugAndSlug' => $offerTagsByCatSlugAndSlug,
            'offer' => $offer,
            'viewTemplate' => $viewTemplate,
            'addMessageForm' => $addMessageForm,
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

        $traking = new MwsOfferTracking();
        $traking->setOffer($offer);
        $traking->setOwner($user);

        $comment = $request->request->get('comment', '--');
        // if (!$comment) {
        //     throw $this->createNotFoundException("Missing comment");
        // }
        $traking->setComment($comment);

        $offerStatusSlug = $request->request->get('offerStatusSlug', '--');
        if ($offerStatusSlug && strlen($offerStatusSlug)) {
            $offer->setCurrentStatusSlug($offerStatusSlug);
            $this->em->persist($offer);
            $traking->setOfferStatusSlug($offerStatusSlug);
        }

        $this->em->persist($traking);
        $this->em->flush(); // TIPS : Sync Generated ID in DB for new traking

        // No need of below, inverse relationship of setOffer take care of it
        // $offer->addMwsOfferTracking($traking);
        // $this->em->persist($offer);
        // $this->em->flush();

        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return $this->json([
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-offer-add-comment')->getValue(),
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
        '/import/{viewTemplate<[^/]*>?}/{format}',
        name: 'mws_offer_import',
        methods: ['GET', 'POST'],
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
        MwsContactRepository $mwsContactRepository,
        SluggerInterface $slugger,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $maxTime = 60 * 30; // 30 minutes max
        set_time_limit($maxTime);
        ini_set('max_execution_time', $maxTime);

        $forceRewrite = $request->query->get('forceRewrite', false);
        $forceStatusRewrite = $request->query->get('forceStatusRewrite', false);
        $forceCleanTags = $request->query->get('forceCleanTags', false);
        $forceCleanContacts = $request->query->get('forceCleanContacts', false);

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

                    $newFilename = $safeFilename . '_' . uniqid() . '.' . $extension;

                    $importContent = file_get_contents($importedUpload->getPathname());
                    // https://www.php.net/manual/fr/function.iconv.php
                    // https://www.php.net/manual/en/function.mb-detect-encoding.php
                    // $importContent = iconv("ISO-8859-1", "UTF-8", $importContent);
                    $importContentEncoding = mb_detect_encoding($importContent);
                    // dd($importContentEncoding);
                    $importContent = iconv($importContentEncoding, "UTF-8", $importContent);
                    // dd($importContent);
                    // TIPS : clean as soon as we can...
                    unlink($importedUpload->getPathname());
                    // $reportSummary = $importContent;
                    /** @var MwsOffer[] */
                    $offersDeserialized = $this->deserializeOffers(
                        $user,
                        $importContent,
                        $format,
                        $newFilename,
                        $mwsOfferRepository,
                        $mwsOfferStatusRepository,
                        $mwsContactRepository,
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
                                $sync = function ($path) use ($inputOffer, $offer) {
                                    $set = 'set' . ucfirst($path);
                                    $get = 'get' . ucfirst($path);
                                    $v =  $inputOffer->$get();
                                    if (
                                        null !== $v &&
                                        ((!is_string($v)) || strlen($v))
                                    ) {
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
                                if ($forceCleanTags) {
                                    $offer->getTags()->clear();
                                }
                                if ($forceStatusRewrite) {
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

                                $tags = $inputOffer->getTags();
                                foreach ($tags as $tag) {
                                    // TIPS : inside addTag, only one by specific only one choice category type ?
                                    // OK to copy all, clone src use
                                    // $mwsOfferRepository->addTag($offer, $tag);
                                    // $offer->addTag($tag); // TODO : clean up script in case someone use the add without category dups check ?
                                    // But still safer to use repo algo :
                                    $mwsOfferRepository->addTag($offer, $tag);
                                }

                                if ($forceCleanContacts) {
                                    $offer->getContacts()->clear();
                                }
                                $contacts = $inputOffer->getContacts();
                                foreach ($contacts as $contact) {
                                    $offer->addContact($contact);
                                }

                                // dump($inputOffer);
                                // dd($offer);
                                // CLEAN all possible other duplicates :
                                foreach ($allDuplicates as $otherDups) {
                                    $em->remove($otherDups);
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
                                    $offerStatusSlug = "mws-offer-tags-category-src-import|a-relancer"; // TODO : load from status DB or config DB...
                                    $offer->setCurrentStatusSlug($offerStatusSlug);
                                    // dd($offer);    
                                }
                            } else {
                                $reportSummary .= "<strong>Ignore le doublon : </strong> [$sourceName,  $slug]<br/>";
                                continue; // TODO : WHY BELOW counting one write when all is duplicated ?
                            }
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
        MwsContactRepository $mwsContactRepository
    ) {
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
