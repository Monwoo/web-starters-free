<?php

namespace MWS\MoonManagerBundle\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;
use MWS\MoonManagerBundle\Entity\MwsTimeTag;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment as TwigEnv;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @extends ServiceEntityRepository<MwsOffer>
 *
 * @method MwsOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsOffer[]    findAll()
 * @method MwsOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsOfferRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected LoggerInterface $logger,
        protected TwigEnv $twig,
        protected FormFactoryInterface $formFactory,
        protected MwsOfferStatusRepository $mwsOfferStatusRepository,
        protected MwsTimeTagRepository $mwsTimeTagRepository,
    ) {
        parent::__construct($registry, MwsOffer::class);
    }

    public function fetchMwsAddOfferForm($offer = null)
    {
        // dd($this->container);
        // $tagSlugSep = '|'; // TODO :load objects and trick display/value function of surveyJS allowing '|' separator instead... ?
        $tagSlugSep = ' > ';
        $allSourceNames = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT
                o.sourceName as value,
                o.sourceName as label
            ")
            ->where("o.sourceName IS NOT NULL")
            // TODO : 'value as label' not working. Why and do tips below...
            ->orderBy('o.sourceName', 'ASC')
            ->getQuery()->getResult();
        $allClientNames = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT
                o.clientUsername as value,
                o.clientUsername as label
            ")
            ->where("o.clientUsername IS NOT NULL")
            ->orderBy('o.clientUsername', 'ASC')
            ->getQuery()->getResult();

            $allOfferSlugs = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT
                o.slug as value,
                o.slug as label
            ")
            ->where("o.slug IS NOT NULL")
            ->orderBy('o.slug', 'ASC')
            ->getQuery()->getResult();
            
        $allOfferTags = array_map(
            function (array $tagResp) use ($tagSlugSep) {
                $tag = $tagResp[0];
                $slug = $tag->getCategorySlug() . $tagSlugSep . $tag->getSlug();
                // dd($slug);
                return $slug;
                // return [
                //   "value" => $slug,
                //   "label" => $tag->getCategorySlug() . " > " . $tag->getSlug(),
                // ];
            },
            // $mwsOfferStatusRepository->findAll()
            $this->mwsOfferStatusRepository
                ->createQueryBuilder("s")
                // https://stackoverflow.com/questions/8233746/concatenate-with-null-values-in-sql
                ->select("s, CONCAT(COALESCE(s.categorySlug, ''), s.slug) AS slugKey")
                // ->where($qb->expr()->isNotNull("t.categorySlug"))
                // ->orderBy("t.categorySlug")
                // ->addOrderBy("t.slug")
                ->addOrderBy("slugKey")
                ->getQuery()->getResult()
        );
        // dd($allClientNames);
        $allOfferBudgets = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT o.budget as value
            ")
            ->where("o.clientUsername IS NOT NULL")
            ->orderBy('o.budget', 'ASC')
            ->getQuery()->getResult();

        $tagQb = $this->mwsTimeTagRepository->createQueryBuilder("t")
            ->orderBy('LOWER(t.slug)');
        $timingTags = $tagQb->getQuery()->getResult();
        $allTimingTags = array_map(
            function (MwsTimeTag $tag) {
                return $tag->getSlug();
            },
            $timingTags
        );

        $mwsAddOfferConfig = [
            // TODO : serialize offer ?
            "jsonResult" => rawurlencode($offer ? json_encode($offer) : '{}'),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsOfferAddType.json.twig",
                [
                    'allSourceNames' => $allSourceNames,
                    'allClientNames' => $allClientNames,
                    'allOfferSlugs' => $allOfferSlugs,
                    'allOfferTags' => $allOfferTags,
                    'allOfferBudgets' => $allOfferBudgets,
                    'allTimingTags' => $allTimingTags,
                ]
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $mwsAddOfferForm = $this->createForm(MwsSurveyJsType::class, $mwsAddOfferConfig);
        return $mwsAddOfferForm;
    }

    // TODO doc : should use this function.... instead of entity->addTag....
    public function addTag($offer, $tag)
    {
        if (!$tag) {
            $this->logger->debug("Ignore add as empty tag for  : " . $offer->getSlug());
            return;
        }
        // TODO or TIPS ? : do in some Doctrine postpersiste listener ? well, no event on addTag right ?
        $em = $this->getEntityManager();
        /** @var MwsOfferStatusRepository */
        $tagsRepo =  $em->getRepository(MwsOfferStatus::class);
        if ($tag->getCategorySlug()) {
            $category = $tagsRepo->findOneWithSlugAndCategory(
                $tag->getCategorySlug(),
                null // TIPS : OK since only ONE LEVEL of category
            );
            if ($category && !$category->isCategoryOkWithMultiplesTags()) {
                // dd($offer->getTags());
                // Avoid tags duplicate if category avoid multiples, clean up first
                $tagsToRemove = [];
                foreach ($offer->getTags() as $existingTag) {
                    if (
                        $tag->getCategorySlug()
                        === $existingTag->getCategorySlug()
                    ) {
                        $tagsToRemove[] = $existingTag;
                    }
                }
                foreach ($tagsToRemove as $t) {
                    // $offer->getTags()->remove($id);
                    $offer->removeTag($t);
                }
            }
        }
        $offer->addTag($tag);
    }

    // TODO : doc : will return ALL category and sub category tags
    public function getExtendedTags($offer)
    {
        $extendedTags = [];
        $tags = $offer->getTags();

        $em = $this->getEntityManager();
        /** @var MwsOfferStatusRepository */
        $tagsRepo =  $em->getRepository(MwsOfferStatus::class);

        foreach ($tags as $tag) {
            $extendedTags[] = $tag;
            if ($tag->isCategoryOkWithMultiplesTags()) {
                // TODO : more like a repository function, need to seek tags...
                $extendedTags[] = $tagsRepo->findBy([
                    'categorySlug' => $tag->getCategorySlug()
                ]);
            }
        }
    }

    public function applyOfferLokup($qb, $offerLookup, $slotName = 'o')
    {
        if (!$offerLookup) {
            return;
        }
        [
            "searchKeyword" => $searchKeyword,
            "searchBudgets" => $searchBudgets,
            "searchStart" => $searchStart,
            "searchEnd" => $searchEnd,
            "searchTags" => $searchTags,
            "searchTagsToInclude" => $searchTagsToInclude,
            "searchTagsToAvoid" => $searchTagsToAvoid,
            "customFilters" => $customFilters,
        ] = [
            ...[
                // TODO : dynamic from surveyJS json fields instead of ram list + reuse for twig... ?
                "searchKeyword" => null,
                "searchBudgets" => null,
                "searchStart" => null,
                "searchEnd" => null,
                "searchTags" => null,
                "searchTagsToInclude" => null,
                "searchTagsToAvoid" => null,
                "customFilters" => null,
            ],
            ...$offerLookup
        ];

        $tagSlugSep = ' > '; // TODO :load objects and trick display/value function of surveyJS instead...
        // dd($offerLookup);
        if ($searchKeyword) {
            $qb
                // LOWER(REPLACE($slotName.clientUsername, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
                // OR LOWER(REPLACE($slotName.contact1, ' ', '')) LIKE LOWER(REPLACE(:keyword, ' ', ''))
                ->andWhere("
                LOWER(REPLACE($slotName.clientUsername, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE($slotName.contact1, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE($slotName.contact2, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE($slotName.contact3, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE($slotName.title, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE($slotName.description, ' ', '')) LIKE :keyword
                OR LOWER(REPLACE($slotName.budget, ' ', '')) LIKE :keyword
            ")
                ->setParameter('keyword', '%' . strtolower(str_replace(" ", "", $searchKeyword)) . '%');
        }

        if (count($searchBudgets ?? [])) {
            $orClause = '';
            foreach ($searchBudgets as $idx => $searchBudget) {
                if ($idx) {
                    $orClause .= ' OR ';
                }
                $orClause .= ":budget$idx = $slotName.budget";
                $qb->setParameter("budget$idx", $searchBudget);
            }

            $qb = $qb->andWhere($orClause);
        }

        if ($searchStart && strlen($searchStart)) {
            $searchStart = (new DateTime($searchStart));
            $qb->andWhere("$slotName.leadStart > :searchStart")
                ->setParameter('searchStart', $searchStart);
        }
        if ($searchEnd && strlen($searchEnd)) {
            $searchStart = (new DateTime($searchEnd));
            $qb->andWhere("$slotName.leadStart < :searchEnd")
                ->setParameter('searchEnd', $searchEnd);
        }

        if (count($searchTags) || count($searchTagsToAvoid) || count($searchTagsToInclude)) {
            $qb = $qb
                ->innerJoin("$slotName.tags", 'tag');
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
                // + TODO : join search on $slotName.tags instead ? 
                // $orClause .= "$slotName.currentStatusSlug = :tag$idx";
                // $orClause .= ":tag$idx in ($slotName.tags)";
                if ($category) {
                    $orClause .= "( :tagSlug$idx = tag.slug AND ";
                    $qb->setParameter("tagSlug$idx", $slug);
                } else {
                    // Otherwise : fetch all tags from category
                    $category = $slug;
                    $orClause .= "( ";
                }
                $orClause .= ":tagCategory$idx = tag.categorySlug )";
                // $qb->setParameter("tag$idx", str_replace($tagSlugSep, '|', $tag));
                // $qb->setParameter("tag$idx", $tag);
                $qb->setParameter("tagCategory$idx", $category);
            }

            $qb = $qb->andWhere($orClause);
        }

        if ($searchTagsToInclude && count($searchTagsToInclude)) {
            // dd($searchTagsToAvoid);
            foreach ($searchTagsToInclude as $idx => $slug) {
                [$category, $slug] = explode($tagSlugSep, $slug);
                $criteria = [
                    'categorySlug' => $category ? $category : $slug,
                ];
                if ($category) {
                    $criteria['slug'] = $slug;
                }
                $tags = $this->mwsOfferStatusRepository->findBy($criteria);

                // No meaning to force include of all tags for exclusive category,
                // will only have one of it... ok for other category ?
                // not tested yet, only exclusive category for now...
                // So will 'OR' the force include to contain at least one
                // tag of root category :
                $dql = '( ';
                foreach ($tags as $tIdx => $tag) {
                    if ($tIdx) {
                        $dql .= ' OR ';
                    }
                    $dql .= ":tagToInclude{$idx}_$tIdx MEMBER OF $slotName.tags";
                    $qb->setParameter("tagToInclude{$idx}_$tIdx", $tag);
                    // dd($dql);
                }
                $dql .= ' )';
                $qb = $qb->andWhere($dql);
            }
        }

        // TODO : User Deprecated: Not enabling lazy ghost objects is deprecated and will not be supported in Doctrine ORM 3.0. Ensure Doctrine\ORM\Configuration::setLazyGhostObjectEnabled(true) is called to enable them. (ProxyFactory.php:166 called by EntityManager.php:178, https://github.com/doctrine/orm/pull/10837/, package doctrine/orm)

        if (count($searchTagsToAvoid)) {
            // dd($searchTagsToAvoid);

            foreach ($searchTagsToAvoid as $idx => $searchTagToAvoidSlug) {
                [$category, $slug] = explode($tagSlugSep, $searchTagToAvoidSlug);
                $criteria = [
                    'categorySlug' => $category ? $category : $slug,
                ];
                if ($category) {
                    $criteria['slug'] = $slug;
                }
                $tags = $this->mwsOfferStatusRepository->findBy($criteria);
                // dd($tags);
                // dump($category); dd($slug);
                // $dql .= "NOT ( :tagToAvoidSlug$idx = tag.slug";
                // $dql .= " AND :tagToAvoidCategory$idx = tag.categorySlug )";
                // $dql .= "NOT ( :tagToAvoidSlug$idx MEMBER OF $slotName.tags.slug ";
                // $dql .= " AND :tagToAvoidCategory$idx MEMBER OF $slotName.tags.categorySlug )";
                // $qb->setParameter("tagToAvoidSlug$idx", $slug);
                // $qb->setParameter("tagToAvoidCategory$idx", $category);
                foreach ($tags as $tIdx => $tag) {
                    $dql = '';
                    $dql .= ":tagToAvoid{$idx}_$tIdx NOT MEMBER OF $slotName.tags";
                    $qb->setParameter("tagToAvoid{$idx}_$tIdx", $tag);
                    // dd($dql);
                    $qb = $qb->andWhere($dql);
                }
            }
        }

        if (count($customFilters)) {
            // $lastWeekDate = new DateTime(getDate(strtotime("-1 week"));
            $lastWeekDate = new DateTime("-1 week");
            $qb = $qb
                ->join("$slotName.contacts", 'contact');
            // dd($customFilters);
            foreach ($customFilters as $customFilter) {
                if ($customFilter === "Ayant une photo") {
                    $qb = $qb->andWhere("contact.avatarUrl IS NOT NULL");
                    $qb = $qb->andWhere("contact.avatarUrl <> ''");
                }
                if ($customFilter === "Moins d'une semaine") {
                    // dd($lastWeekDate);
                    // $qb = $qb->andWhere("CONVERT(DATETIME, $slotName.leadStart) > :startTime")
                    // ->setParameters(['startTime' => $lastWeekDate]);

                    // $qb = $qb->andWhere("$slotName.leadStart > :startTime");
                    // $qb->setParameter('startTime', $lastWeekDate);

                    // ->andWhere('e.date BETWEEN :from AND :to')
                    // ->setParameter('from', $from )
                    // ->setParameter('to', $to)

                    $qb = $qb->andWhere("$slotName.leadStart >= :startTime");
                    $qb->setParameter('startTime', $lastWeekDate);
                }
                if ($customFilter === "Avec un contact") {
                    $qb = $qb->andWhere("
                    $slotName.contact1 IS NOT NULL
                    OR $slotName.contact2 IS NOT NULL
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 <> ''
                    OR $slotName.contact2 <> ''
                    ");
                }
                if ($customFilter === "Avec un contact à ouvrir") {
                    $qb = $qb->andWhere("
                    $slotName.contact1 IS NOT NULL
                    OR $slotName.contact2 IS NOT NULL
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 <> ''
                    OR $slotName.contact2 <> ''
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 LIKE :seeEmailLookup
                    OR $slotName.contact1 LIKE :seePhoneLookup
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact2 LIKE :seeEmailLookup
                    OR $slotName.contact2 LIKE :seePhoneLookup
                    ");
                    $qb->setParameter('seeEmailLookup', "%Voir l'adresse email%")
                        ->setParameter('seePhoneLookup', "%Voir le téléphone%");;
                }
                if ($customFilter === "Avec un contact ouvert") {
                    $qb = $qb->andWhere("
                    $slotName.contact1 IS NOT NULL
                    OR $slotName.contact2 IS NOT NULL
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 <> ''
                    OR $slotName.contact2 <> ''
                    ");
                    $qb = $qb->andWhere("
                    NOT ($slotName.contact1 LIKE :seeEmailLookup
                    OR $slotName.contact1 LIKE :seePhoneLookup)
                    ");
                    $qb = $qb->andWhere("
                    NOT ($slotName.contact2 LIKE :seeEmailLookup
                    OR $slotName.contact2 LIKE :seePhoneLookup)
                    ");

                    $qb->setParameter('seeEmailLookup', "%Voir l'adresse email%")
                        ->setParameter('seePhoneLookup', "%Voir le téléphone%");;
                    // dd($qb->getQuery()->getDQL());
                }
                if ($customFilter === "Avec un téléphone à ouvrir") {
                    $qb = $qb->andWhere("
                    $slotName.contact1 IS NOT NULL
                    OR $slotName.contact2 IS NOT NULL
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 <> ''
                    OR $slotName.contact2 <> ''
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 LIKE :seePhoneLookup
                    OR $slotName.contact2 LIKE :seePhoneLookup
                    ");
                    $qb->setParameter('seePhoneLookup', "%Voir le téléphone%");;
                    // dd($qb->getQuery()->getDQL());
                }
                if ($customFilter === "Avec un téléphone ouvert") {
                    $qb = $qb->andWhere("
                    $slotName.contact1 IS NOT NULL
                    OR $slotName.contact2 IS NOT NULL
                    ");
                    $qb = $qb->andWhere("
                    $slotName.contact1 <> ''
                    OR $slotName.contact2 <> ''
                    ");
                    $qb = $qb->andWhere("
                    NOT ($slotName.contact1 LIKE :seeEmailLookup
                    OR $slotName.contact1 LIKE :seePhoneLookup)
                    ");
                    // OR $slotName.contact1 LIKE :emailSign)
                    // ");
                    $qb = $qb->andWhere("
                    NOT ($slotName.contact2 LIKE :seeEmailLookup
                    OR $slotName.contact2 LIKE :seePhoneLookup)
                    ");

                    $qb->setParameter('seeEmailLookup', "%Voir l'adresse email%")
                        ->setParameter('seePhoneLookup', "%Voir le téléphone%");
                        // $qb->setParameter('emailSign', "%@%");
                        // $qb->setParameter('phoneSign', "%+%"); // Indicatif.
                    ;
                    // dd($qb->getQuery()->getDQL());
                    // https://www.sqlite.org/lang_expr.html#regexp
                    // TODO : version of SQLITE ? + SQLITE FUNCTION, using sql one avoid it's usage for sqlite ?
                    // $qb = $qb->andWhere("
                    // (REGEXP($slotName.contact1, :phoneRegexp) = 0
                    // OR REGEXP($slotName.contact2, :phoneRegexp) = 0)
                    // ");
                    // $qb->setParameter('phoneRegexp', "[0-9 +()]+");
                    $qb = $qb->andWhere("
                        ($slotName.contact1 LIKE :phoneSign
                        OR $slotName.contact2 LIKE :phoneSign)
                    ");
                    $qb->setParameter('phoneSign', "%+%"); // Indicatif.

                }
                if ($customFilter === "Sans offre déposée") {
                    // $qb = $qb->andWhere("
                    // $slotName.sourceDetail.monwooOfferId IS NOT NULL
                    // ");
                    // https://dev.mysql.com/doc/refman/5.7/en/json-search-functions.html#function_json-contains
                    // $qb = $qb->andWhere("
                    //  NOT JSON_CONTAINS($slotName.sourceDetail, '', '$.monwooOfferId')
                    // "); // MySql....

                    // https://github.com/ScientaNL/DoctrineJsonFunctions
                    // JSON_EXTRACT(c.attributes, '$.gender') = :gender
                    $qb = $qb->andWhere("
                     JSON_EXTRACT($slotName.sourceDetail, '$.monwooOfferId') IS NULL
                    ");
                }
                if ($customFilter === "Avec offre déposée") {
                    $qb = $qb->andWhere("
                     NOT JSON_EXTRACT($slotName.sourceDetail, '$.monwooOfferId') IS NULL
                    ");
                }
                if ($customFilter === "Est favoris") {
                    $qb = $qb->andWhere("
                     JSON_EXTRACT($slotName.sourceDetail, '$.isBookmark') = :isBookmark
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
                    //  JSON_EXTRACT($slotName.sourceDetail,
                    //     CONCAT('$.messages[',JSON_LENGTH('$.messages') - 1,']')
                    //  ) LIKE :msgOwner
                    // ")

                    // $qb = $qb->andWhere("
                    //     JSON_EXTRACT($slotName.sourceDetail,
                    //     " . $qb->expr()->concat(
                    //         "'$.messages['", $qb->expr()->concat(
                    //             // "'0'",// "JSON_LENGTH('$.messages')", // "$slotName.clientUsername", // "'0'",
                    //             $qb->expr()->diff("JSON_LENGTH('$.messages')", 1),
                    //             "']'"
                    //         )
                    //     ) . "
                    //     ) LIKE :msgOwner
                    // ")

                    $qb = $qb->andWhere("
                        (
                            JSON_EXTRACT($slotName.sourceDetail,
                                MWS_CONCAT(
                                    '$.messages[',
                                    IIF(JSON_LENGTH($slotName.sourceDetail, '$.messages') > 0,
                                        JSON_LENGTH($slotName.sourceDetail, '$.messages') - 1,
                                        0
                                    ),
                                    ']'
                                )
                            ) NOT LIKE :msgOwner
                        ) OR (
                            JSON_EXTRACT($slotName.sourceDetail,
                                MWS_CONCAT(
                                    '$.messages[',
                                    IIF(JSON_LENGTH($slotName.sourceDetail, '$.messages') > 0,
                                        JSON_LENGTH($slotName.sourceDetail, '$.messages') - 1,
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
                    //     JSON_LENGTH($slotName.sourceDetail, '$.messages') > 0
                    // ");

                    // dd($qb->getQuery()->getSQL());
                    // dd($qb->expr()->);
                }
                if ($customFilter === "Ordonner par dernier tchat") {
                    // $qb = $qb->andWhere("
                    //  JSON_EXTRACT($slotName.sourceDetail, '$.messages[-1]') IS TRUE
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
                        JSON_EXTRACT($slotName.sourceDetail, '$.projectOffers')
                    ");

                    // TODO : Remove other order by tags from KNP bundle ? or combine ?
                    // $qb->addOrderBy($request->query->get('sort'),$request->query->get('direction'));
                    // $request->query->remove('sort');
                    // $request->query->remove('direction');
                }
                if ($customFilter === "Ordonner par meilleure taux de réponse") {
                    // $qb = $qb->andWhere("
                    //  JSON_EXTRACT($slotName.sourceDetail, '$.isBookmark') IS TRUE
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


                    // $qb = $qb->select("o, (1.0 * JSON_EXTRACT($slotName.sourceDetail, '$.projectOffersViewed')
                    // / JSON_EXTRACT($slotName.sourceDetail, '$.projectOffers')) as clientAnswerRatio");
                    // $qb = $qb->orderBy("
                    //     clientAnswerRatio
                    // ", "DESC");

                    // TIPS :
                    // // Will ERROR :
                    // 1.0 * JSON_EXTRACT($slotName.sourceDetail, '$.projectOffersViewed')
                    // // Will be int casted, always 0 :
                    // JSON_EXTRACT($slotName.sourceDetail, '$.projectOffersViewed')
                    // / JSON_EXTRACT($slotName.sourceDetail, '$.projectOffers') * 1.0

                    $qb = $qb->orderBy("
                        JSON_EXTRACT($slotName.sourceDetail, '$.projectOffersViewed') * 1.0
                        / JSON_EXTRACT($slotName.sourceDetail, '$.projectOffers')
                    ", "DESC");

                    // TODO : Remove other order by tags from KNP bundle ? or combine ?
                    // $this->request->query->remove('sort');
                    // $request->query->remove('direction');
                }
            }
        }
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     */
    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        // An exception has been thrown during the rendering of a template 
        // ("The "form.factory" service or alias has been removed or inlined 
        // when the container was compiled. You should either make it public,
        // or stop using the container directly and use dependency injection instead.").
        // return $this->container->get('form.factory')->create($type, $data, $options);
        // php bin/console debug:container | grep 'form.factory'
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * Returns a rendered view.
     *
     * Forms found in parameters are auto-cast to form views.
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        // TODO : container is missing twig ?
        // if (!$this->container->has('twig')) {
        //     throw new \LogicException('You cannot use the "renderView" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        // }

        foreach ($parameters as $k => $v) {
            if ($v instanceof FormInterface) {
                $parameters[$k] = $v->createView();
            }
        }

        // return $this->container->get('twig')->render($view, $parameters);
        return $this->twig->render($view, $parameters);
    }

    //    /**
    //     * @return MwsOffer[] Returns an array of MwsOffer objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MwsOffer
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
