<?php
// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Controller;

use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Entity\MwsMessageTchatUpload;
use MWS\MoonManagerBundle\Entity\MwsTimeQualif;
use MWS\MoonManagerBundle\Entity\MwsTimeSlot;
use MWS\MoonManagerBundle\Entity\MwsTimeSlotUpload;
use MWS\MoonManagerBundle\Entity\MwsTimeTag;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Repository\MwsTimeQualifRepository;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotRepository;
use MWS\MoonManagerBundle\Repository\MwsTimeTagRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use PHPUnit\Util\Json;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\SecurityBundle\Security;
// use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route(
    '/{_locale<%app.supported_locales%>}/mws-timings',
    options: ['expose' => true],
)]
#[SecuAttr(
    "is_granted('ROLE_USER')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsTimingController extends AbstractController
{
    public function __construct(
        protected Security $security,
        protected LoggerInterface $logger,
        protected SerializerInterface $serializer,
        protected TranslatorInterface  $translator,
        protected EntityManagerInterface $em,
        protected KernelInterface $kernel,
        protected SluggerInterface $slugger,
        protected ParameterBagInterface $params,
        protected UploaderHelper $UploaderHelper,
        protected ?string $thumbUploadUriPrefix = null,
        protected ?string $thumbUploadFolder = null,
    ) {
    }

    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $c = parent::setContainer($container);

        $projectDir = $this->params->get('kernel.project_dir');
        $uploadSubFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
        // TODO : will break with 
        // packages/mws-moon-manager/src/Naming/OrignalNameNamer.php
        // only using "$projectDir/$uploadSubFolder/timings/thumbs"
        $this->thumbUploadFolder = $thumbUploadFolder
            ?? "$projectDir/$uploadSubFolder/timings/thumbs";
        $thumbUploadUriPrefix = $thumbUploadUriPrefix
            ?? $this->UploaderHelper->asset([
                'mediaName' => ' ',
                'mediaFile' => [
                    'filename' => ' ',
                    'basename' => ' ',
                ]
            ], 'mediaFile', MwsTimeSlotUpload::class);
        $this->thumbUploadUriPrefix = trim($thumbUploadUriPrefix);

        return $c;
    }



    #[Route('/qualif/view/{viewTemplate<[^/]*>?}', name: 'mws_timings_qualif')]
    public function qualif(
        $viewTemplate,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
        MwsTimeQualifRepository $mwsTimeQualifRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        /** @var MwsUser $user */
        $user = $this->getUser();
        if (!$user) {
            // TIPS : redondant, but better if used without routing system secu...
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $requestData = $request->query->all();
        $searchKeyword = $requestData['searchKeyword'] ?? null;
        $searchStart = $requestData['searchStart'] ?? null;
        $searchEnd = $requestData['searchEnd'] ?? null;

        $searchTags = $requestData['searchTags'] ?? []; // []);
        $searchTagsToInclude = $requestData['searchTagsToInclude'] ?? []; // []);
        $searchTagsToAvoid = $requestData['searchTagsToAvoid'] ?? []; // []);

        // $tagQb = $mwsTimeTagRepository->createQueryBuilder("t");
        $allTagsList = $mwsTimeTagRepository->findAllTagsForContext();

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                "searchKeyword" => $searchKeyword,
                "searchStart" => $searchStart,
                "searchEnd" => $searchEnd,
                "searchTags" => $searchTags,
                "searchTagsToInclude" => $searchTagsToInclude,
                "searchTagsToAvoid" => $searchTagsToAvoid,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsTimingLookupType.json.twig",
                [
                    'allTimingTags' => array_map(
                        function (MwsTimeTag $tag) {
                            return $tag->getSlug();
                        },
                        $allTagsList
                    ),
                    // 'allTimingTags' => array_map(
                    //     function (MwsTimeTag $tag) {
                    //         return $tag->getSlug();
                    //     },
                    //     $tagQb->where($tagQb->expr()->isNotNull("t.category"))
                    //         ->getQuery()->getResult()
                    // ),
                ]
            )),
        ];
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
                $searchKeyword = $surveyAnswers['searchKeyword'] ?? null;
                $searchStart = $surveyAnswers['searchStart'] ?? null;
                $searchEnd = $surveyAnswers['searchEnd'] ?? null;
                $searchTags = $surveyAnswers['searchTags'] ?? [];
                $searchTags = $surveyAnswers['searchTags'] ?? [];
                $searchTagsToInclude = $surveyAnswers['searchTagsToInclude'] ?? [];
                $searchTagsToAvoid = $surveyAnswers['searchTagsToAvoid'] ?? [];
                // dd($searchTags);
                return $this->redirectToRoute(
                    'mws_timings_qualif',
                    array_merge($request->query->all(), [
                        "viewTemplate" => $viewTemplate,
                        "searchKeyword" => $searchKeyword,
                        "searchStart" => $searchStart,
                        "searchEnd" => $searchEnd,
                        "searchTags" => $searchTags,
                        "searchTagsToInclude" => $searchTagsToInclude,
                        "searchTagsToAvoid" => $searchTagsToAvoid,
                        "page" => 1,
                    ]),
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        $qb = $mwsTimeSlotRepository->createQueryBuilder('s');
        $mwsTimeSlotRepository->applyTimingLokup($qb, [
            'searchKeyword' => $searchKeyword,
            "searchStart" => $searchStart,
            "searchEnd" => $searchEnd,
            'searchTags' => $searchTags,
            'searchTagsToInclude' => $searchTagsToInclude,
            'searchTagsToAvoid' => $searchTagsToAvoid,
        ]);

        $qb->orderBy("s.sourceTimeGMT", "ASC");

        $query = $qb->getQuery();
        // dd($query->getResult());
        // $pageLimit = $request->query->getInt('pageLimit', 124);
        $pageLimit = $request->query->getInt('pageLimit', 0);
        /*page limit */
        // dd($pageLimit);
        // Below will setup for inside php
        // $request->query->set('pageLimit', $pageLimit);
        // Redirect for update :
        $uConfig = $user->getConfig();
        if ($pageLimit <= 0) {
            $defaultPLimit = $uConfig['pageLimit'] ?? 124; // TODO : from logged user configs first ok + crm default for 124 ? also used Svelte side
            return $this->redirectToRoute(
                'mws_timings_qualif',
                array_merge($request->query->all(), [
                    "pageLimit" => $defaultPLimit,
                    // "sourceRootLookupUrl" => $sourceRootLookupUrl,
                ]),
                Response::HTTP_SEE_OTHER
            );
        } else {
            $uConfig['pageLimit'] = $pageLimit;
            $user->setConfig($uConfig);
        }

        $timings = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            // $request->query->getInt('pageLimit', 448), /*page limit, 28*16 */
            $pageLimit,
        );

        $this->logger->debug("Succeed to list timings");

        $timeQualifs = $mwsTimeQualifRepository->findAll();
        $allTagsList = $mwsTimeTagRepository->findAllTagsForContext();
        // $allTagsList = $mwsTimeTagRepository->findBy([], [
        //     // TODO : translated label imports/edits (multilingues)
        //     'label' => 'ASC'
        // ]);
        // TODO : no DQL nat sort ?
        // https://www.mysqltutorial.org/mysql-basics/mysql-natural-sorting/
        // https://sqlite.org/forum/info/d5cf6c6317dd7e7f
        // https://stackoverflow.com/questions/20431345/naturally-sort-a-multi-dimensional-array-by-key

        // array_multisort( // TIPS
        //     // array_keys($allTagsList),
        //     array_map(function ($t) {
        //         return $t->getLabel();
        //     }, $allTagsList),
        //     SORT_NATURAL | SORT_FLAG_CASE,
        //     $allTagsList
        // );

        return $this->render('@MoonManager/mws_timing/qualif.html.twig', [
            'timings' => $timings,
            'userConfig' => $user->getConfig(),
            'allTagsList' => $allTagsList,
            'timeQualifs' => $timeQualifs,
            'lookupForm' => $filterForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route('/report/{viewTemplate<[^/]*>?}', name: 'mws_timings_report')]
    public function report(
        $viewTemplate,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
        MwsTimeQualifRepository $mwsTimeQualifRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            // TIPS : redondant, but better if used without routing system secu...
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $requestData = $request->query->all();
        $searchKeyword = $requestData['searchKeyword'] ?? null;
        // TIPS : default start date minus 1 month
        // To AVOID huge dataset computing (one year
        // is like 7 sec server side + 40 sec load client side
        // for 65000 time slots)
        // https://stackoverflow.com/questions/7068707/how-do-i-remove-3-months-from-a-date
        // $searchStart = $requestData['searchStart'] ?? (new DateTime())->sub(new DateInterval(
        //     "3 months"
        // ))->format('c');
        // $new_timestamp = strtotime('-3 months', strtotime($date));
        // TODO : new DateTimeZone('Europe/Paris')
        //       should comme from user preference and
        //       default to client web browser config
        //       if not fixed in logged user configs...
        $mostRecentSlot = $mwsTimeSlotRepository->findOneBy(
            [],
            ['sourceTimeGMT' => 'DESC']
        );
        $mostRecentDate = $mostRecentSlot?->getsourceTimeGMT()
            ?? new DateTime(
                'now',
                new DateTimeZone('Europe/Paris')
            );
        // dd($request->query->count());
        // Suggest start only for FIRST page load 
        // (to avoid too much overloads)
        //     => TODO : also limit in max number of items ?
        //     => or Limit on max num of items and check right
        //     date to get it, since num of slot is not end user
        //     friendly, not meanfull to say 'x slot' for time qualif...
        $suggestedStart = $request->query->count()
            ? null
            : $mostRecentDate->modify(
                "-1 months"
            )->format('Y-m-d\TH:i');

        $searchStart = $requestData['searchStart'] ?? $suggestedStart;
        // dd($searchStart);
        // dd($searchStart);
        $searchEnd = $requestData['searchEnd'] ?? null;

        $searchTags = $requestData['searchTags'] ?? []; // []);
        $searchTagsToInclude = $requestData['searchTagsToInclude'] ?? []; // []);
        $searchTagsToAvoid = $requestData['searchTagsToAvoid'] ?? []; // []);

        $tagQb = $mwsTimeTagRepository->createQueryBuilder("t")
            ->orderBy('LOWER(t.slug)');

        // $timingTags = array_map(
        //     function (MwsTimeTag $tag) {
        //         return $tag->getSlug();
        //     },
        //     $tagQb // ->where($tagQb->expr()->isNotNull("s.category"))
        //         ->getQuery()->getResult()
        // );
        $timingTags = $tagQb->getQuery()->getResult();

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                // TODO : createForm 
                // $form = $formFactory->createNamed('custom_form_name', CustomType::class);
                // or : https://stackoverflow.com/questions/37005899/symfony3-is-it-possible-to-change-the-name-of-a-form
                // add public function getBlockPrefix() in form type....
                "MwsTimingLookupType" => true,
                "searchKeyword" => $searchKeyword,
                "searchStart" => $searchStart,
                "searchEnd" => $searchEnd,
                "searchTags" => $searchTags,
                "searchTagsToInclude" => $searchTagsToInclude,
                "searchTagsToAvoid" => $searchTagsToAvoid,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsTimingLookupType.json.twig",
                [
                    'allTimingTags' => array_map(
                        function (MwsTimeTag $tag) {
                            return $tag->getSlug();
                        },
                        $timingTags
                    ),
                ]
            )),
        ];
        $filterForm = $this->createForm(MwsSurveyJsType::class, $lastSearch);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted()) {
            $this->logger->debug("Did submit search form");

            if ($filterForm->isValid()) {
                $this->logger->debug("Search form ok");

                // dd($filterForm->get('surveyJsModel'));
                // dd($filterForm);
                $surveyAnswers = json_decode(
                    urldecode($filterForm->get('jsonResult')->getData()),
                    true
                );
                if ($surveyAnswers['MwsTimingLookupType'] ?? false) {
                    $searchKeyword = $surveyAnswers['searchKeyword'] ?? null;
                    $searchStart = $surveyAnswers['searchStart'] ?? null;
                    $searchEnd = $surveyAnswers['searchEnd'] ?? null;
                    $searchTags = $surveyAnswers['searchTags'] ?? [];
                    $searchTags = $surveyAnswers['searchTags'] ?? [];
                    $searchTagsToInclude = $surveyAnswers['searchTagsToInclude'] ?? [];
                    $searchTagsToAvoid = $surveyAnswers['searchTagsToAvoid'] ?? [];
                    // dd($searchTags);
                    return $this->redirectToRoute(
                        'mws_timings_report',
                        array_merge($request->query->all(), [
                            "viewTemplate" => $viewTemplate,
                            "searchKeyword" => $searchKeyword,
                            "searchStart" => $searchStart,
                            "searchEnd" => $searchEnd,
                            "searchTags" => $searchTags,
                            "searchTagsToInclude" => $searchTagsToInclude,
                            "searchTagsToAvoid" => $searchTagsToAvoid,
                            "page" => 1,
                        ]),
                        Response::HTTP_SEE_OTHER
                    );
                }
            }
        }

        $reportTagsLvl1 = $requestData['lvl1Tags'] ?? []; // []);
        $reportTagsLvl2 = $requestData['lvl2Tags'] ?? []; // []);
        $reportTagsLvl3 = $requestData['lvl3Tags'] ?? []; // []);
        $reportTagsLvl4 = $requestData['lvl4Tags'] ?? []; // []);
        $reportTagsLvl5 = $requestData['lvl5Tags'] ?? []; // []);

        $lastReport = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                "MwsTimingReportType" => true,
                "lvl1Tags" => $reportTagsLvl1,
                "lvl2Tags" => $reportTagsLvl2,
                "lvl3Tags" => $reportTagsLvl3,
                "lvl4Tags" => $reportTagsLvl4,
                "lvl5Tags" => $reportTagsLvl5,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsTimingReportType.json.twig",
                [
                    'allTimingTags' => array_map(
                        function (MwsTimeTag $tag) {
                            return $tag->getSlug();
                        },
                        $timingTags
                    ),
                ]
            )),
        ];
        $reportForm = $this->createForm(MwsSurveyJsType::class, $lastReport);
        $reportForm->handleRequest($request);

        if ($reportForm->isSubmitted()) {
            $this->logger->debug("Did submit search form");

            if ($reportForm->isValid()) {
                $this->logger->debug("Search form ok");
                // dd($reportForm);
                $surveyAnswers = json_decode(
                    urldecode($reportForm->get('jsonResult')->getData()),
                    true
                );
                if ($surveyAnswers['MwsTimingReportType'] ?? false) {
                    $reportTagsLvl1 = $surveyAnswers['lvl1Tags'] ?? [];
                    $reportTagsLvl2 = $surveyAnswers['lvl2Tags'] ?? [];
                    $reportTagsLvl3 = $surveyAnswers['lvl3Tags'] ?? [];
                    $reportTagsLvl4 = $surveyAnswers['lvl4Tags'] ?? [];
                    $reportTagsLvl5 = $surveyAnswers['lvl5Tags'] ?? [];
                    // dd($reportTagsLvl1);
                    return $this->redirectToRoute(
                        'mws_timings_report',
                        array_merge($request->query->all(), [
                            "viewTemplate" => $viewTemplate,
                            "page" => 1,
                            "lvl1Tags" => $reportTagsLvl1,
                            "lvl2Tags" => $reportTagsLvl2,
                            "lvl3Tags" => $reportTagsLvl3,
                            "lvl4Tags" => $reportTagsLvl4,
                            "lvl5Tags" => $reportTagsLvl5,
                        ]),
                        Response::HTTP_SEE_OTHER
                    );
                }
            }
        }

        $qb = $mwsTimeSlotRepository->createQueryBuilder('s');
        // https://stackoverflow.com/questions/17878237/doctrine-cannot-select-entity-through-identification-variables-without-choosing
        // ->from(MwsTimeTag::class, 'tag');
        // CONCAT_WS('-', tag.slug, tag.slug) as tags,
        // strftime('%W', s.sourceTimeGMT) as sourceWeekOfYear,

        $mwsTimeSlotRepository->applyTimingLokup($qb, [
            'searchKeyword' => $searchKeyword,
            "searchStart" => $searchStart,
            "searchEnd" => $searchEnd,
            'searchTags' => $searchTags,
            'searchTagsToInclude' => $searchTagsToInclude,
            'searchTagsToAvoid' => $searchTagsToAvoid,
        ]);

        $qb->orderBy("s.sourceTimeGMT", "ASC");

        // $query = $qb->getQuery();
        // $qb = $qb->innerJoin('s.tags', 'tag');
        // https://stackoverflow.com/questions/45756622/doctrine-query-with-nullable-optional-join
        $qb = $qb->leftJoin('s.tags', 'tag');

        // Fetching 'source' is too slow, and splitting with , might have issue with ','...
        // GROUP_CONCAT(s.source) as source,            
        // strftime('%Y-%m-%d %H:%M:%S', s.sourceTimeGMT) as sourceTimeGMT,


        // https://www.php.net/manual/fr/function.strftime.php
        // GROUP_CONCAT(tag.pricePerHr) as pricesPerHr,
        $qb = $qb->select("
            count(s) as count,
            strftime('%Y-%m-%d', s.sourceTimeGMT) as sourceDate,
            strftime('%s', s.sourceTimeGMT) as sourceTimeGMTstamp,
            strftime('%Y', s.sourceTimeGMT) as sourceYear,
            strftime('%m', s.sourceTimeGMT) as sourceMonth,
            strftime('%d', s.sourceTimeGMT) as sourceWeekOfYear,
            GROUP_CONCAT(tag.slug) as tags,
            GROUP_CONCAT(s.maxPath, '#_;_#') as maxPath,
            GROUP_CONCAT(s.sourceStamp) as sourceStamps,
            GROUP_CONCAT(tag.label) as labels,
            GROUP_CONCAT(s.rangeDayIdxBy10Min) as allRangeDayIdxBy10Min,
            GROUP_CONCAT(s.thumbnailJpeg, '#_;_#') as thumbnailJpegs,
            GROUP_CONCAT(s.id) as ids
        ");

        $qb->groupBy("sourceYear");
        $qb->addGroupBy("sourceMonth");
        $qb->addGroupBy("sourceDate");
        $qb->addGroupBy("tag.slug");
        // $qb->addGroupBy("s.rangeDayIdxBy10Min");

        $groupedQuery = $qb->getQuery();
        // dd($query->getDQL());    
        // dd($query->getResult());    
        // $timingsReport = $paginator->paginate(
        //     $groupedQuery, /* query NOT result */
        //     $request->query->getInt('page', 1), /*page number*/
        //     // $request->query->getInt('pageLimit', 448), /*page limit, 28*16 */
        //     $request->query->getInt('pageLimit', 200000), /*page limit */
        // ); // TIPS : will REMOVE the groupBy etc.... (select too ?)...
        $timingsReport = $groupedQuery->getResult();

        $timeSlotDbCount = $mwsTimeSlotRepository
        ->createQueryBuilder('s')
        ->select('count(s.id)')
        ->getQuery()
        ->getSingleScalarResult();

        // // TODO : too slow :
        // $timingsPage = $paginator->paginate(
        //     $query, /* query NOT result */
        //     $request->query->getInt('page', 1), /*page number*/
        //     // $request->query->getInt('pageLimit', 448), /*page limit, 28*16 */
        //     $request->query->getInt('pageLimit', 200000), /*page limit */
        // );
        // // TODO : too slow :
        // // $timings = iterator_to_array($timingsPage->getItems());
        // // $timingsById = [];
        // // array_walk(
        // //     $timings,
        // //     function ($t) use (&$timingsById) {
        // //         $timingsById[$t->getId()] = $t;
        // //     }
        // // );

        $this->logger->debug("Succeed to list timings");

        return $this->render('@MoonManager/mws_timing/report.html.twig', [
            'timingsReport' => $timingsReport,
            'timeSlotDbCount' => $timeSlotDbCount,
            // 'timingsById' => $timingsById,
            // 'timings' => $timingsPage,
            'timingTags' => $timingTags,
            'lookupForm' => $filterForm,
            'report' => [
                "lvl1Tags" => $reportTagsLvl1,
                "lvl2Tags" => $reportTagsLvl2,
                "lvl3Tags" => $reportTagsLvl3,
                "lvl4Tags" => $reportTagsLvl4,
                "lvl5Tags" => $reportTagsLvl5,
            ],
            'reportForm' => $reportForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    protected function getThumbPath($request, $url)
    {
        // protected function getThumbUrl($request, $path) {
        // TIPS : below not advised since http request might be blocked for security
        //         + dev server will not serve more than one request at a time,
        //         // inside request will not run...
        // return $request->getSchemeAndHttpHost()
        // . $request->getBaseURL()
        // . implode('/', array_map('rawurlencode', explode('/', $path)));
        // . implode('/', array_map('urlencode', explode('/', $path)));
        // dump($this->thumbUploadUriPrefix);
        // dump($this->thumbUploadFolder);
        $relativePath = trim(str_replace($this->thumbUploadUriPrefix, "", $url), "/");
        // dump($relativePath);
        // dump($url);
        // dd($this->thumbUploadUriPrefix);
        // return "file://{$this->thumbUploadFolder}/$relativePath";
        $localPath = "{$this->thumbUploadFolder}/$relativePath";
        // dd($localPath);
        return "$localPath";
    }

    protected function getCurlContext(Request $request)
    {
        // https://stackoverflow.com/questions/30628361/php-basic-auth-file-get-contents
        // $h = "";
        // // implode(";\n", array_re $request->headers->all()
        // foreach ($request->headers->all() as $hk => $hv) {
        //     dd($hv);
        //     $h .= "$hk: $hv\n";
        // }
        // $h = '' . $request->headers;
        // if (!$headers = $request->headers->all()) {
        //     return '';
        // }
        // ksort($headers);
        // $max = max(array_map('strlen', array_keys($headers))) + 1;
        // $content = '';
        // foreach ($headers as $name => $values) {
        //     $name = ucwords($name, '-');
        //     foreach ($values as $value) {
        //         // $content .= sprintf("%-{$max}s %s\r\n", $name.':', $value);
        //         $content .= sprintf("%-{$max}s %s\r\n", $name.':', $value);
        //     }
        // }
        $content = 'Cookie: ' . $request->headers->get('Cookie');

        $curlContext = stream_context_create([
            "http" => [
                "header" => $content
            ]
        ]);
        return $curlContext;
    }

    // TIPS : 6 month of Miguel Monwoo workload
    //       is around 500Mo with thumb 100
    // public const defaultThumbSize = 100;
    // With half size, it will be ~300Mo
    public const defaultThumbSize = 42;
    #[Route('/fetch-media-url', name: 'mws_timing_fetchMediatUrl')]
    public function fetchRootUrl(
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $projectDir = $this->params->get('kernel.project_dir');
        // $thumbSubFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
        // $thumbFolder = "$projectDir/$thumbSubFolder/timings/thumbs";
        $curlContext = $this->getCurlContext($request);

        $url = $request->get('url', null);
        $url = rawurldecode($url);
        $keepOriginalSize = $request->query->get('keepOriginalSize', null);
        $thumbnailSize = intval($request->query->get('thumbnailSize', 0));
        if (!$thumbnailSize) {
            // TODO : default from session or db config params ?
            $thumbnailSize = self::defaultThumbSize;
        }
        // dd($thumbnailSize);
        $this->logger->debug("Will fetch url : $url");
        $timingId = $request->get('timingId', null);

        if (str_starts_with($url, "file://")) {
            $inputPath = explode("file://", $url)[1] ?? null;
            $path = realpath($inputPath);
            if (!$path) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $path = realpath("$projectDir/$inputPath");
                $this->logger->debug("Fixed root url", [$inputPath, $path]);
            }
            // TODO : secu : filter real path to 
            //        allowed screenshot folders from .env only ?
            // dd($path);
            $this->logger->debug("Root file", [$inputPath, $path]);
            if (!$path || !file_exists($path)) {
                // throw $this->createAccessDeniedException('Media path not allowed');
                // throw $this->createNotFoundException('Media path not allowed');
                $path = null; // Next try catch will take care, to failback on timing id thumb if fail
            }
            $url = $path;
        }
        if (!$url) {
            // TIPS : avoid throw if don't want to fill up error logs :
            // throw $this->createNotFoundException('Fail for url ' . $url);
            return new Response('Fail for url ' . $url, 404);
        }

        // Or use : https://symfony.com/doc/current/http_client.html
        // $respData = file_get_contents($url);

        // TODO : for efficiency, resize image before usage :
        // https://www.php.net/manual/en/imagick.resizeimage.php
        // or :
        // https://stackoverflow.com/questions/14649645/resize-image-in-php
        // https://www.php.net/manual/en/function.imagecopyresized.php (GD)
        // 
        // and/or js size ? : 
        // https://github.com/fabricjs/fabric.js
        // https://imagekit.io/blog/how-to-resize-image-in-javascript/
        // https://stackoverflow.com/questions/39762102/resizing-image-while-printing-html
        // $imagick = new \Imagick(realpath($url));
        try {
            if ($keepOriginalSize) {
                // TODO : filter url outside of allowed server folders ?
                $respData = file_get_contents($url);
            } else {
                $imagick = new \Imagick($url);
                $targetW = $thumbnailSize; // px,
                $factor = $targetW / $imagick->getImageWidth();
                $imagick->resizeImage( // TODO : desactivate with param for qualif detail view ?
                    $imagick->getImageWidth() * $factor,
                    $imagick->getImageHeight() * $factor,
                    // https://urmaul.com/blog/imagick-filters-comparison/
                    \Imagick::FILTER_CATROM,
                    0
                );
                $imagick->setImageCompressionQuality(0);
                // https://www.php.net/manual/fr/imagick.resizeimage.php#94493
                // FILTER_POINT is 4 times faster
                // $imagick->scaleimage(
                //     $imagick->getImageWidth() * 4,
                //     $imagick->getImageHeight() * 4
                // );
                $respData = $imagick->getImageBlob();
                $imagick->clear();
            }
        } catch (\Exception $e) {
            $this->logger->warning($e);
            // Try to failback with thumbnail :
            if ($timingId) {
                $s = $mwsTimeSlotRepository->findOneBy([
                    'id' => $timingId
                ]);
                $thumb = $s?->getThumbnailJpeg() ?? '';
                if (starts_with($thumb, '/') && file_exists($tUrl = $this->getThumbPath($request, $thumb))) {
                    // TODO : or use 'file://' system instead of '/' system ?
                    $respData = file_get_contents($tUrl, false, $curlContext);
                } else {
                    $b64Parts = explode(';base64,', $thumb);
                    $respData = base64_decode($b64Parts[1]) ?? null;
                }
            }
            // dd($e);
            if (!$respData) {
                // TIPS : avoid throw if don't want to fill up error logs :
                // throw $this->createNotFoundException('Fail for url ' . $url);
                return new Response('Fail for url ' . $url, 404);
            }
            // return new Response('', 415);
            // return new Response('');
        }

        $response = new Response($respData);
        $response->headers->set('Content-Type', 'image/jpg');
        // https://symfony.com/doc/6.2/the-fast-track/en/21-cache.html
        $maxAge = 3600 * 5;
        $response->setSharedMaxAge($maxAge);
        // max-age=604800, must-revalidate
        $response->headers->set('Cache-Control', "max-age=$maxAge");
        $response->headers->set('Expires', "$maxAge");
        // For legacy browsers (no cache):
        // $response->headers->set('Pragma', 'no-chache');

        // $response->headers->set('Content-Type', 'application/pdf');
        // $mime = [
        //     'json' => 'application/json',
        //     'csv' => 'text/comma-separated-values',
        //     'xml' => 'application/xml',
        //     'yaml' => 'application/yaml',
        // ][$format] ?? 'text/plain';
        // if ($mime) {
        //     $response->headers->set('Content-Type', $mime);
        // }

        // $response->headers->set('Cache-Control', 'no-cache');
        // $response->headers->set('Pragma', 'no-chache');
        // $response->headers->set('Expires', '0');
        return $response;
    }

    #[Route(
        '/delete-all/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_delete_all',
        methods: ['POST'],
    )]
    public function deleteAll(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) { // TODO : only for admin too ?
            $this->logger->debug("Fail auth", []);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-timing-delete-all', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $searchKeyword = $request->get('searchKeyword', null);
        $searchStart = $request->get('searchStart', null);
        $searchEnd = $request->get('searchEnd', null);
        $searchTags = $request->get('searchTags', []); // []);
        $searchTagsToInclude = $request->get('searchTagsToInclude', []); // []);
        $searchTagsToAvoid = $request->get('searchTagsToAvoid', []); // []);

        $qb = $this->em->createQueryBuilder('s')
        ->delete(MwsTimeSlot::class, 's');
        $mwsTimeSlotRepository->applyTimingLokup($qb, [
            'searchKeyword' => $searchKeyword,
            "searchStart" => $searchStart,
            "searchEnd" => $searchEnd,
            'searchTags' => $searchTags,
            'searchTagsToInclude' => $searchTagsToInclude,
            'searchTagsToAvoid' => $searchTagsToAvoid,
        ]);
    
        $query = $qb->getQuery();
        $query->execute();
        $this->em->flush();

        // if($request->isXmlHttpRequest()) {
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return $this->json([
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-delete-all')->getValue(),
                'viewTemplate' => $viewTemplate,
            ]);
        }
        return $this->redirectToRoute(
            'mws_timings_qualif',
            [ // array_merge($request->query->all(), [
                "viewTemplate" => $viewTemplate,
                "page" => 1,
            ], //),
            Response::HTTP_SEE_OTHER
        );
    }


    #[Route(
        '/export/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_export',
        methods: ['POST', 'GET'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function export(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        UploaderHelper $uploaderHelper,
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
        $curlContext = $this->getCurlContext($request);

        $format = $request->get('format') ?? 'yaml';
        $timingLookup = $request->get('timingLookup');
        $attachThumbnails = $request->get('attachThumbnails');
        $thumbnailsSize = intval($request->get('thumbnailsSize', self::defaultThumbSize));
        // Avoid 0 size case :
        $thumbnailsSize = $thumbnailsSize ? $thumbnailsSize : self::defaultThumbSize;
        // var_dump($attachThumbnails); exit;

        // $tSlots = $mwsTimeSlotRepository->findAll() ?? [];

        $qb = $mwsTimeSlotRepository->createQueryBuilder('s');

        if ($timingLookup) {
            $timingLookup = json_decode($timingLookup, true);
            $mwsTimeSlotRepository->applyTimingLokup($qb, $timingLookup);
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
                    "MwsTimingController did process "
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
                    ...($attachThumbnails ? [] : ['thumbnailJpeg']),
                    'id'
                ],
                // ObjectNormalizer::IGNORED_ATTRIBUTES => ['tags']
                AbstractNormalizer::CALLBACKS => [
                    'sourceStamp' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) use ($tryFlush) {
                        // dd('ok'); 
                        // TIPS : use some attributes that will never be null
                        //        otherwise, the hacky callback will not trigger...
                        $tryFlush();
                        /** @var MwsTimeSlot $outerObject */
                        return $outerObject->getSourceStamp();
                    },
                    'thumbnailJpeg' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) use (
                        $attachThumbnails,
                        $tryFlush,
                        $em,
                        $uploaderHelper,
                        $thumbnailsSize,
                        $self,
                        $request,
                        $curlContext,
                    ) {
                        // dd('ok');
                        $tryFlush();
                        // dump($innerObject);
                        // var_dump($attachThumbnails); exit;
                        // dd($outerObject);
                        if ($attachThumbnails) { // already ignored, juste in case
                            // dump($innerObject);
                            // dd($outerObject);
                            $thumbAlreadyOk = false;
                            /** @var MwsTimeSlot $outerObject */
                            if ($outerObject->getThumbnailJpeg()) {
                                try {
                                    $imagick = new \Imagick();
                                    $thumb = $outerObject->getThumbnailJpeg() ?? '';
                                    if (starts_with($thumb, '/') && file_exists($tUrl = $this->getThumbPath($request, $thumb))) {
                                        $respData = file_get_contents($this->getThumbPath($request, $thumb), false, $curlContext);
                                        $imagick->readImageBlob($respData);
                                    } else {
                                        $b64Parts = explode(';base64,', $thumb);
                                        $imagick->readImageBlob(base64_decode($b64Parts[1] ?? null));
                                    }
                                    $thumbAlreadyOk = $imagick->getImageWidth() === $thumbnailsSize;
                                    // dump($imagick->getImageWidth());
                                    // dump($thumbnailsSize);    
                                    $imagick->clear();
                                } catch (Exception $e) {
                                    $this->logger->debug(
                                        "Will rebuild Thumb, contain wrong img data : " . $e->getMessage()
                                    );
                                }
                                // dd($thumbAlreadyOk);
                            }
                            if (!$thumbAlreadyOk) {
                                // Routing.generate("mws_timing_fetchMediatUrl", {
                                //     url: "file://" + timingSlot.source.path,
                                //     keepOriginalSize: 1,
                                //   });
                                //   $path = 'myfolder/myimage.png';
                                //   $type = pathinfo($path, PATHINFO_EXTENSION);
                                // $thumbUrl = $self->generateUrl("mws_timing_fetchMediatUrl", [
                                //     'url' => "file://" . $outerObject->getSource()['path'],
                                // ], UrlGeneratorInterface::ABSOLUTE_URL);

                                $type  = 'jpeg';
                                // dd($request->headers->all());
                                // $headersText = "";
                                // foreach ($request->headers->all() as $key => $header) {
                                //     $headersText .= "$key: {$header[0]}\r\n";
                                // }
                                // dd($headersText);

                                // https://www.hashbangcode.com/article/using-authentication-and-filegetcontents
                                // $context = stream_context_create(array(
                                //     'http' => array(
                                //         'header'  => $headersText, // "Authorization: Basic " . base64_encode("$username:$password")
                                //     )
                                // ));
                                // // will fail to open on single dev server...
                                // $data = file_get_contents($thumbUrl, false, $context);
                                // dd($data);

                                // https://stackoverflow.com/questions/61011582/override-content-of-a-subrequest-in-symfony4
                                // $request = $this->container->get('request_stack')->getCurrentRequest();
                                // dd($request->attributes);
                                $attr = [
                                    // '_controller' => 'MwsMoonManager:MwsTimingController:fetchRootUrl',
                                    '_controller' => MwsTimingController::class . '::fetchRootUrl',
                                    // "_route" => "mws_timing_export"
                                    // "_route_params" => array:2 [
                                    //   "viewTemplate" => null
                                    //   "_locale" => "fr"
                                    // ]
                                ];
                                $params = [
                                    'url' => "file://" . $outerObject->getSource()['path'],
                                    'thumbnailSize' => $thumbnailsSize ? $thumbnailsSize : self::defaultThumbSize,
                                ];
                                $subRequest = $request->duplicate($params, null, $attr);
                                // if ($isPost) {
                                //     $subRequest = $this->duplicateRequestForPost($request, $params, $attr);
                                // } else {
                                // }

                                /** @var Response $resp */
                                $resp = $this->container->get('http_kernel')
                                    ->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
                                $em->flush();
                                $data = $resp->getContent();
                                // dd($resp);
                                if (404 === $resp->getStatusCode()) {
                                    $data = null;
                                    if ($innerObject) {
                                        // dd($thumb);                                   
                                        if (starts_with($thumb, '/') && file_exists($tUrl = $this->getThumbPath($request, $thumb))) {
                                            // $respData = file_get_contents($tUrl, false, $curlContext);
                                            // $imagick->readImageBlob($respData);
                                            // TODO : need $context = stream_context_create([ etc... to transfert auth credentials
                                            // https://stackoverflow.com/questions/30628361/php-basic-auth-file-get-contents
                                            // dd($curlContext);
                                            // https://stackoverflow.com/questions/21755377/urlencode-everything-but-slashes
                                            $innerData = file_get_contents($this->getThumbPath($request, $innerObject), false, $curlContext);
                                            // dd($curlContext);
                                            // dd("ok" . !!$innerData);
                                        } else {
                                            $b64 = explode(
                                                'data:image/' . $type . ';base64,',
                                                $innerObject,
                                                2
                                            )[1] ?? '';
                                            // dd($b64);
                                            $innerData = base64_decode($b64);
                                        }
                                        if ($innerData && strlen($innerData)) {
                                            $imagick = new \Imagick();
                                            $imagick->readImageBlob($innerData);
                                            // TODO : default thumbsize from app param or user db config ?
                                            $factor = ($thumbnailsSize ? $thumbnailsSize : self::defaultThumbSize) / $imagick->getImageWidth();
                                            // dd($factor);
                                            $imagick->resizeImage( // TODO : desactivate with param for qualif detail view ?
                                                $imagick->getImageWidth() * $factor,
                                                $imagick->getImageHeight() * $factor,
                                                // https://urmaul.com/blog/imagick-filters-comparison/
                                                \Imagick::FILTER_CATROM,
                                                0
                                            );
                                            $imagick->setImageCompressionQuality(0);
                                            $data = $imagick->getImageBlob();
                                            $imagick->clear();
                                        }
                                    }
                                } else {
                                    $data = $resp->getContent();
                                }
                                // dd($base64);
                                if (json_decode($_SERVER['STORE_THUMBNAIL_IN_DB'] ?? 'false')) {
                                    $base64 = $data
                                        ? 'data:image/' . $type . ';base64,' . base64_encode($data)
                                        : null;
                                    $outerObject->setThumbnailJpeg($base64);
                                } else {
                                    $upload = new MwsTimeSlotUpload();
                                    $projectDir = $this->params->get('kernel.project_dir');
                                    $subFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
                                    $uploadSrc = "$projectDir/$subFolder/timings/thumbs";
                                    // if (!file_exists($uploadSrc)) {
                                    //     mkdir($uploadSrc, 0777, true);
                                    // }

                                    // $tmp = tempnam($uploadSrc, $outerObject->getSourceStamp()); // tmpfile(); // $this->createTmpFile();
                                    // $tmp = $uploadSrc . DIRECTORY_SEPARATOR . $outerObject->getSourceStamp(); // tmpfile(); // $this->createTmpFile();
                                    // $tmp = realpath($uploadSrc . DIRECTORY_SEPARATOR . $outerObject->getSourceStamp()); // tmpfile(); // $this->createTmpFile();
                                    // TODO : same as real path without file_exists check ?
                                    // $tmp = realpath($uploadSrc . DIRECTORY_SEPARATOR . $outerObject->getSourceStamp()); // tmpfile(); // $this->createTmpFile();
                                    $tmp = $uploadSrc . DIRECTORY_SEPARATOR . $outerObject->getSourceStamp(); // tmpfile(); // $this->createTmpFile();
                                    // TIPS : need NAME change for copy to work :
                                    // dd($tmp);
                                    // $tmp = $uploadSrc . DIRECTORY_SEPARATOR . 'TMP-' .
                                    $tmp = $uploadSrc . DIRECTORY_SEPARATOR .
                                        str_replace('/', '_', str_replace($uploadSrc . DIRECTORY_SEPARATOR, '', $tmp)); // tmpfile(); // $this->createTmpFile();
                                    // dd($tmp);
                                    // TODO : secu only inside target folder....
                                    // TODO : using relative path as name make some tools breaks...

                                    if (!file_exists(dirname($tmp))) {
                                        mkdir(dirname($tmp), 0777, true);
                                    }

                                    file_put_contents($tmp, $data);

                                    // $uploadUrl = $uploadHelper->asset([
                                    //     'mediaName' => ' ',
                                    //     'mediaFile' => [
                                    //         'filename' => ' ',
                                    //         'basename' => ' ',
                                    //     ]
                                    // ], 'mediaFile', MwsMessageTchatUpload::class);

                                    // $newMedia = new ReplacingFile($tmp); // will ignore subfolders
                                    // $newMedia = new UploadedFile($tmp, dirname($tmp), 'image/jpeg', \UPLOAD_ERR_OK); // will ignore subfolders
                                    $newMedia = new class($tmp, dirname($tmp), 'image/jpeg', \UPLOAD_ERR_OK) extends UploadedFile
                                    {
                                        public function isValid(): bool
                                        {
                                            return true;
                                        }
                                        public function move(string $directory, string $name = null): File
                                        {
                                            // TIPS : do nothing for our use case, file is already generated at right place, no need to move
                                            $target = $this->getTargetFile($directory, $name);
                                            return $target;
                                        }
                                    };
                                    // $newMedia = $uploaderHelper->asset([
                                    //     'path' => $tmp,
                                    //     'mediaName' => "thumbs/" . $outerObject->getSourceStamp(),
                                    //     'mediaFile' => [
                                    //         'filename' => "thumbs/" . $outerObject->getSourceStamp(),
                                    //         'basename' => "thumbs/" . $outerObject->getSourceStamp(),
                                    //         'pathname' => $tmp,
                                    //     ]
                                    // ], 'mediaFile', MwsMessageTchatUpload::class);
                                    // $newMedia = new ReplacingFile($tmp); // will ignore subfolders
                                    // $newMedia->setFilename( "thumbs/" . $outerObject->getSourceStamp());

                                    // $newMedia = new class(
                                    //     $tmp,
                                    //     "thumbs/" . $outerObject->getSourceStamp(),
                                    //     "$projectDir/$subFolder/messages/tchats" 
                                    // ) extends ReplacingFile {
                                    //     public function __construct(string $path, protected string $filename, protected string $upRoot, bool $checkPath = true)
                                    //     {
                                    //         parent::__construct($path, $checkPath);
                                    //         // $this->filename = $filename;
                                    //     }
                                    //     // public function getFilename():string {
                                    //     //     return $this->filename;
                                    //     // }
                                    //     public function getPath():string {
                                    //         return $this->upRoot;
                                    //     }
                                    // }; // will ignore subfolders

                                    // dd($newMedia);
                                    // dump($tmp);
                                    // dd(file_exists( $tmp));
                                    // dump($newMedia);
                                    $upload->setMediaFile(
                                        $newMedia
                                    );
                                    $em->persist($upload); // This one copy file on doctrine events
                                    // unlink($tmp); // No need of tmp anymore
                                    $uploadUrl = $uploaderHelper->asset($upload, 'mediaFile', MwsTimeSlotUpload::class);
                                    $uploadUrl = str_replace($request->getBasePath(), '', $uploadUrl);
                                    // dd($uploadUrl);
                                    $outerObject->setThumbnailJpeg($uploadUrl);
                                }
                                $em->persist($outerObject);
                            }

                            $thumb = $outerObject?->getThumbnailJpeg() ?? '';
                            // dump(file_exists($tUrl = $this->getThumbPath($request, $thumb)));
                            // dump($tUrl);
                            // dd($thumb);
                            $type  = 'jpeg';

                            $newThumb = starts_with($thumb, '/') && file_exists($tUrl = $this->getThumbPath($request, $thumb))
                                ? 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($tUrl, false, $curlContext))
                                : $outerObject->getThumbnailJpeg();
                            // dd($newThumb);
                            // $outerObject->setThumbnailJpeg($newThumb);
                            return $newThumb;
                        }
                    },
                    'tags' => function ($objects) {
                        if (is_string($objects)) {
                            // Denormalize (cf timing import, not used by export)
                            throw new Exception("Should not happen for : " . $objects);
                        } else {
                            // Normalise
                            $norm = array_map(
                                function (MwsTimeTag $o) {
                                    // return $o->getSlug();
                                    return ['slug'  => $o?->getSlug()] ?? null;
                                },
                                $objects->toArray() ?? []
                            );
                            sort($norm);
                            return $norm;
                        }
                    },
                    'maxPriceTag' => function ($objects) {
                        if (is_string($objects)) {
                            // Denormalize (cf timing import, not used by export)
                            throw new Exception("Should not happen for : " . $objects);
                        } else {
                            // Normalise
                            return ['slug'  => $objects?->getSlug()] ?? null;
                        }
                    },
                    'maxPath' => function ($objects) {
                        if (is_string($objects)) {
                            // Denormalize (cf timing import, not used by export)
                            throw new Exception("Should not happen for : " . $objects);
                        } else {
                            // Normalise
                            return json_encode($objects);
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
            . "-TimeSlotsExport-" . time() . ".{$format}"; // . '.pdf';

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

    // https://gist.github.com/carnage/68d6263e1844da8dcf4a
    /**
     * @param $path
     * @param string $prefix
     * @return string
     */
    protected function createTmpDir($path, $prefix = '')
    {
        $fs = new Filesystem();
        // TODO : no LockHandler in SF 6 ? or custom for 
        // https://gist.github.com/carnage/68d6263e1844da8dcf4a
        // $lock = new LockHandler(hash('sha256', $path));
        // $lock->lock(true);

        do {
            $dirname = $path . DIRECTORY_SEPARATOR . uniqid($prefix);
        } while ($fs->exists($dirname));

        $fs->mkdir($dirname);

        register_shutdown_function(
            function () use ($dirname, $fs) {
                $fs->remove($dirname);
            }
        );

        // $lock->release();

        return $dirname;
    }
    /**
     * @param $path
     * @param string $prefix
     * @return string
     */
    protected function createTmpFile($path, $prefix = '')
    {
        $fs = new Filesystem();
        // TODO : no LockHandler in SF 6 ?
        // $lock = new LockHandler(hash('sha256', $path));
        // $lock->lock(true);

        do {
            $filename = $path . DIRECTORY_SEPARATOR . uniqid($prefix);
        } while ($fs->exists($filename));

        $fs->touch($filename);

        register_shutdown_function(
            function () use ($filename, $fs) {
                $fs->remove($filename);
            }
        );

        // $lock->release();

        return $filename;
    }

    /**
     * https://stackoverflow.com/questions/61011582/override-content-of-a-subrequest-in-symfony4
     * Just to replace $request->content ...
     */
    private function duplicateRequestForPost(Request $request, array $postParams, array $attributes): Request
    {
        $postRequest = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode($postParams)
        );

        $postRequest->query = $request->query;
        $postRequest->request = $request->request;
        $postRequest->cookies = $request->cookies;
        $postRequest->files = $request->files;
        $postRequest->server = $request->server;

        $postRequest->attributes = new ParameterBag($attributes);

        return $postRequest;
    }

    #[Route(
        '/import/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_import',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function import(
        string|null $viewTemplate,
        Request $request,
        UploaderHelper $uploaderHelper,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        // dd($csrf);
        if (!$this->isCsrfTokenValid('mws-csrf-timing-import', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $maxTime = 60 * 600; // 600 minutes max // TODO : from crm config and/or user config ?
        set_time_limit($maxTime);
        ini_set('max_execution_time', $maxTime);

        // format
        $format = $request->get('format');
        $shouldOverwrite = $request->get('shouldOverwrite');
        $shouldOverwritePriceRules = $request->get('shouldOverwritePriceRules');
        $shouldIdentifyByFilename = $request->get('shouldIdentifyByFilename');
        $shouldRecomputeAllOtherTags = $request->get('shouldRecomputeAllOtherTags');
        $importFile = $request->files->get('importFile');
        $importContent = $importFile ? file_get_contents($importFile->getPathname()) : '[]';
        // $importContent = file_get_contents($importFile->getPathname());
        // https://www.php.net/manual/fr/function.iconv.php
        // https://www.php.net/manual/en/function.mb-detect-encoding.php
        // $importContent = iconv("ISO-8859-1", "UTF-8", $importContent);
        $importContentEncoding = mb_detect_encoding($importContent);
        // dd($importContentEncoding);
        $importContent = iconv($importContentEncoding, "UTF-8", $importContent);

        $importReport = '';

        $pendingNewTags = [];
        $tagsUpdatedByFirstMaxPrice = [];
        $em = $this->em;
        $self = $this;
        // dd($shouldOverwritePriceRules);
        /** @var MwsTimeSlot[] $importSlots */
        $importSlots = $this->serializer->deserialize(
            $importContent,
            MwsTimeSlot::class . "[]",
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
                    ) use ($mwsTimeTagRepository, &$importReport, &$pendingNewTags, $em) {
                        // dump($context['deserialization_path']);
                        // if (is_array($innerObject)) {
                        if ($context['deserialization_path'] ?? null) {
                            // dd($innerObject); // TODO ; can't have raw input string ?
                            // throw new Exception("TODO : ");
                            return array_filter(
                                array_map(function ($tagSlug)
                                use ($mwsTimeTagRepository, &$importReport, &$pendingNewTags, &$context, $em) {
                                    $tag = $mwsTimeTagRepository->findOneBy([
                                        'slug' => $tagSlug->getSlug(),
                                    ]);
                                    // dd($tag);
                                    // TODO ; if null tag ?
                                    if (!$tag) {
                                        if ($tagSlug->getSlug() && strlen($tagSlug->getSlug())) {
                                            $importReport .= "Missing tag for slug {$tagSlug->getSlug()} for {$context['deserialization_path']} <br/>";
                                            $pendingNewTags[$tagSlug->getSlug()] = true;
                                            $tag = new MwsTimeTag();
                                            $tag->setSlug($tagSlug->getSlug());
                                            $tag->setLabel("#{$tagSlug->getSlug()}#");
                                            // TIPS : even if will be saved with 'cascade persiste' attribute
                                            // save it now to see it on others imports lookups instead of at
                                            // end of full import query builds...
                                            $em->persist($tag);
                                            $em->flush();
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
                    'maxPriceTag' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) use ($mwsTimeTagRepository, &$importReport, &$pendingNewTags, $em) {
                        if ($context['deserialization_path'] ?? null && $innerObject->getSlug()) {
                            // dd($innerObject);
                            $slug = $innerObject?->getSlug() ?? '';
                            $tag = $mwsTimeTagRepository->findOneBy([
                                'slug' => $slug,
                            ]);
                            if (!$tag && strlen($slug)) {
                                // dd($importReport);
                                $importReport .= "Missing max tag for slug $slug <br/>";
                                $pendingNewTags[$slug] = true;
                                $tag = new MwsTimeTag();
                                $tag->setSlug($slug);
                                $tag->setLabel("#$slug#");
                                // TIPS : even if will be saved with 'cascade persiste' attribute
                                // save it now to see it on others imports lookups instead of at
                                // end of full import query builds...
                                $em->persist($tag);
                                $em->flush();
                            }
                            return $tag;
                        } else {
                            // Normalise (cf timing export, not used by import)
                            throw new Exception("Should not happen");
                        }
                    },
                    'maxPath' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) use (
                        &$importReport,
                        &$tagsUpdatedByFirstMaxPrice,
                        &$pendingNewTags,
                        $mwsTimeTagRepository,
                        $shouldOverwritePriceRules,
                        $self,
                    ) {
                        // TODO : should 
                        // dump($innerObject);
                        // dump($attributeName);
                        // dump($context);
                        // dd($outerObject);
                        if ($context['deserialization_path'] ?? null) {
                            $maxPath = json_decode($innerObject, true);
                            // $slug = $maxPathı[''];
                            if ($maxPath && count($maxPath)) {
                                // dd($importReport);
                                $slug = $maxPath['maxTagSlug'];

                                if (!($tagsUpdatedByFirstMaxPrice[$slug] ?? null)) {
                                    $tag = $mwsTimeTagRepository->findOneBy([
                                        'slug' => $slug,
                                    ]);
                                    if (!$tag || ($pendingNewTags[$slug] ?? null) || $shouldOverwritePriceRules) {
                                        $ruleIdx = $maxPath['maxRuleIndex'] ?? 0;
                                        $importReport .= "Price rule '$ruleIdx' generated from max tag for slug $slug from {$context['deserialization_path']} <br/>";
                                        if (!$tag) {
                                            $tag = new MwsTimeTag();
                                        }
                                        $tag->setSlug($slug);
                                        $tag->setLabel("#$slug#");
                                        // $self->logger->debug("Will update with price rule $ruleIdx", $maxPath);
                                        // Merge with existent...
                                        $tag->setPricePerHrRules([
                                            ...($tag->getPricePerHrRules() ?? []),
                                            $ruleIdx => [
                                                'price' => floatval($maxPath['maxValue']),
                                                'withTags' => array_flip($maxPath['maxSubTags'] ?? []),
                                                'maxLimitPriority' => floatval($maxPath['maxLimitPriority'] ?? 0),
                                            ]
                                        ]);
                                        // unset($pendingNewTags[$slug]);        
                                        $self->em->persist($tag);
                                        $self->em->flush();
                                    }
                                    // TIPS : below will only match and update with
                                    // FIRST max value, more efficient, but if new
                                    // rules for same tag come out below, will not be seen with :
                                    // $tagsUpdatedByFirstMaxPrice[$slug] = true;
                                }
                            }
                            return $maxPath;
                        } else {
                            // Normalise (cf timing export, not used by import)
                            throw new Exception("Should not happen");
                        }
                    },
                    // // TOO early, need to know other props
                    // 'thumbnailJpeg' => function (
                    //     $innerObject,
                    //     $outerObject,
                    //     string $attributeName,
                    //     string $format = null,
                    //     array $context = []
                    // ) use (
                    //     $em,
                    //     $uploaderHelper,
                    //     $self,
                    //     $request,
                    // ) {
                    //     // dd($innerObject);
                    //     dd($context);
                    //     $newThumb = $innerObject;
                    //     if (json_decode($_SERVER['STORE_THUMBNAIL_IN_DB'] ?? 'false')) {
                    //         // $base64 = $data
                    //         //     ? 'data:image/' . $type . ';base64,' . base64_encode($data)
                    //         //     : null;
                    //         // $outerObject->setThumbnailJpeg($innerObject);
                    //     } else {
                    //         // TODO : code factorisation with export
                    //         $data = $newThumb;
                    //         if (starts_with($data, 'data:image')) {
                    //             $b64Parts = explode(';base64,', $data);
                    //             $data = base64_decode($b64Parts[1]) ?? null;

                    //             $upload = new MwsTimeSlotUpload();
                    //             $projectDir = $this->params->get('kernel.project_dir');
                    //             $subFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
                    //             $uploadSrc = "$projectDir/$subFolder/timings/thumbs";
                    //             $tmp = $uploadSrc . DIRECTORY_SEPARATOR . $outerObject->getSourceStamp(); // tmpfile(); // $this->createTmpFile();
                    //             $tmp = $uploadSrc . DIRECTORY_SEPARATOR .
                    //                 str_replace('/', '_', str_replace($uploadSrc . DIRECTORY_SEPARATOR, '', $tmp)); // tmpfile(); // $this->createTmpFile();

                    //             if (!file_exists(dirname($tmp))) {
                    //                 mkdir(dirname($tmp), 0777, true);
                    //             }

                    //             file_put_contents($tmp, $data);
                    //             $newMedia = new class($tmp, dirname($tmp), 'image/jpeg', \UPLOAD_ERR_OK) extends UploadedFile
                    //             {
                    //                 public function isValid(): bool
                    //                 {
                    //                     return true;
                    //                 }
                    //                 public function move(string $directory, string $name = null): File
                    //                 {
                    //                     // TIPS : do nothing for our use case, file is already generated at right place, no need to move
                    //                     $target = $this->getTargetFile($directory, $name);
                    //                     return $target;
                    //                 }
                    //             };
                    //             $upload->setMediaFile(
                    //                 $newMedia
                    //             );
                    //             $em->persist($upload); // This one copy file on doctrine events
                    //             $uploadUrl = $uploaderHelper->asset($upload, 'mediaFile', MwsTimeSlotUpload::class);
                    //             $uploadUrl = str_replace($request->getBasePath(), '', $uploadUrl);
                    //             $newThumb = $uploadUrl;
                    //         }
                    //     }
                    //     $em->persist($outerObject);

                    //     dd($newThumb);
                    //     // $outerObject->setThumbnailJpeg($newThumb);
                    //     return $newThumb;
                    // },
                ],
            ]
        );

        // dd($importSlots);
        $importNewCount = 0;
        $overwriteCount = 0;
        /** @var MwsTimeSlot $importSlot */
        foreach ($importSlots as $idx => $importSlot) {
            // $slot = $mwsTimeSlotRepository->findOneBy([
            //     // TODO : only put basename for unicity check in sourceStamp ?
            //     // => allow change of folders for file name...
            //     // => + check md5 or filesize for 2nd unicity check ?
            //   'sourceStamp' => $importSlot->getSourceStamp()
            // ]);
            $timingLookup = $request->get('timingLookup');

            $qb = $mwsTimeSlotRepository->createQueryBuilder('s')
                ->setMaxResults(1);

            if ($timingLookup) {
                // TODO : will duplicate ? should be ok or wrong to filter imports ?
                // $timingLookup = json_decode($timingLookup, true);
                // $mwsTimeSlotRepository->applyTimingLokup($qb, $timingLookup);    
            }

            if ($shouldIdentifyByFilename) {
                $qb = $qb->where('s.sourceStamp LIKE :sourceStamp')
                    ->setParameter('sourceStamp', '%' . basename(
                        $importSlot->getSourceStamp()
                    ));
            } else {
                $qb = $qb->where('s.sourceStamp = :sourceStamp')
                    ->setParameter(
                        'sourceStamp',
                        $importSlot->getSourceStamp()
                    );
            }
            $slot = $qb->getQuery()->getResult()[0] ?? null;

            if ($slot) {
                if ($shouldOverwrite && $shouldOverwrite != 'null') {
                    $sync = function ($path) use (&$slot, &$importSlot) {
                        $get = 'get' . ucfirst($path);
                        if (!method_exists($importSlot, $get)) {
                            $get = 'is' . ucfirst($path);
                        }
                        $v =  $importSlot->$get();
                        if (null !== $v) {
                            $set = 'set' . ucfirst($path);
                            if (!method_exists($slot, $set)) {
                                // Is collection :
                                $add = 'add' . ucfirst($path);
                                if (!method_exists($slot, $add)) {
                                    $add = preg_replace('/s$/', '', $add);
                                }
                                $collection = $slot->$get();
                                $collection->clear();
                                foreach ($v as $subV) {
                                    $slot->$add($subV);
                                }
                            } else {
                                $slot->$set($v);
                            }
                        }
                    };
                    $timeSlotProps = array_keys(
                        $this->em->getMetadataFactory()
                            ->getMetadataFor(
                                MwsTimeSlot::class
                            )->reflFields
                    );
                    // dd($timeSlotProps);
                    foreach ($timeSlotProps as $property) {
                        $sync($property);
                        // $this->logger->debug("Did load props $property at index $idx");
                    }
                    // $sync('sourceTimeGMT');
                    // $sync('source');
                    // $sync('rangeDayIdxBy10Min');
                    // $sync('maxPriceTag');
                    $importReport .= "Did overwrite [$idx] {$importSlot->getSourceStamp()} <br/>";
                    $importSlot = $slot;
                } else {
                    $importReport .= "Ignore duplicata [$idx] {$importSlot->getSourceStamp()} <br/>";
                    continue;
                }
                $overwriteCount++;
            } else {
                $importNewCount++;
            }


            $newThumb = $importSlot->getThumbnailJpeg();

            if (json_decode($_SERVER['STORE_THUMBNAIL_IN_DB'] ?? 'false')) {
                // $base64 = $data
                //     ? 'data:image/' . $type . ';base64,' . base64_encode($data)
                //     : null;
                // $outerObject->setThumbnailJpeg($innerObject);
            } else {
                // TODO : code factorisation with export
                $data = $newThumb ?? "";
                if (starts_with($data, 'data:image')) {
                    $b64Parts = explode(';base64,', $data);
                    $data = base64_decode($b64Parts[1]) ?? null;

                    $upload = new MwsTimeSlotUpload();
                    $projectDir = $this->params->get('kernel.project_dir');
                    $subFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
                    $uploadSrc = "$projectDir/$subFolder/timings/thumbs";
                    $tmp = $uploadSrc . DIRECTORY_SEPARATOR . $importSlot->getSourceStamp(); // tmpfile(); // $this->createTmpFile();
                    $tmp = $uploadSrc . DIRECTORY_SEPARATOR .
                        str_replace('/', '_', str_replace($uploadSrc . DIRECTORY_SEPARATOR, '', $tmp)); // tmpfile(); // $this->createTmpFile();

                    if (!file_exists(dirname($tmp))) {
                        mkdir(dirname($tmp), 0777, true);
                    }

                    file_put_contents($tmp, $data);
                    $newMedia = new class($tmp, dirname($tmp), 'image/jpeg', \UPLOAD_ERR_OK) extends UploadedFile
                    {
                        public function isValid(): bool
                        {
                            return true;
                        }
                        public function move(string $directory, string $name = null): File
                        {
                            // TIPS : do nothing for our use case, file is already generated at right place, no need to move
                            $target = $this->getTargetFile($directory, $name);
                            return $target;
                        }
                    };
                    $upload->setMediaFile(
                        $newMedia
                    );
                    $em->persist($upload); // This one copy file on doctrine events
                    $uploadUrl = $uploaderHelper->asset($upload, 'mediaFile', MwsTimeSlotUpload::class);
                    $uploadUrl = str_replace($request->getBasePath(), '', $uploadUrl);
                    $newThumb = $uploadUrl;
                }
            }
            if (strlen($newThumb)) {
                $importSlot->setThumbnailJpeg($newThumb);
            }

            // dd($newThumb);

            if (!$importSlot->getSourceStamp()) {
                // TODO : avoid dd ? will break all import, throw custom error ?
                dd('TODO: generate sourceStamp or fail on wrong import format ?');
            }
            $this->em->persist($importSlot);
            $this->em->flush();
        }
        // $newCount = $savedCount - $overwriteCount;
        // $importReport .= "<br/><br/>Enregistrement de $newCount offres OK ($overwriteCount surcharges / $savedCount) <br/>";
        $allCount = $overwriteCount + $importNewCount;
        $importReport .= "Did import <strong>$importNewCount new timings </strong> ($overwriteCount surcharges / $allCount) <br/>";

        if ($shouldRecomputeAllOtherTags) {
            $this->forceTimingsPriceRecompute();
            $importReport .= "Did recompute all timings prices<br/>";
        }

        return $this->json([
            // 'tags' => $tags, // TODO : will force refresh ? should ensure frontend view updates
            // 'tagsGrouped' => $tagsGrouped,
            'importReport' => $importReport,
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-import')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    protected function forceTimingsPriceRecompute()
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'mws:recompute-timing-tags',
        ]);
        $output = new NullOutput();
        $application->run($input, $output);
    }

    #[Route(
        '/tag/list/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_list',
        methods: ['GET'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagList(
        string|null $viewTemplate,
        Request $request,
        PaginatorInterface $paginator,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth", []);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        // $tags = $mwsTimeTagRepository->findAll();
        // array_multisort(
        //     // array_keys($allTagsList),
        //     array_map(function($t) {
        //         return $t->getLabel();
        //     }, $tags),
        //     SORT_NATURAL | SORT_FLAG_CASE,
        //     $tags
        // );

        // dd($this->serializer->serialize($tags, 'yaml'));
        //  TODO : More efficient to 'groupBy' Query with total amount.

        // Fetching all ids is too much time consuming....
        // $tagsSerialized = $this->serializer->serialize($tags, 'yaml', [
        //     'groups' => 'withDeepIds', // TODO : group annotation do not go over ignore annotation
        //     // only below work :
        //     // AbstractNormalizer::ATTRIBUTES => [
        //     //     'id', 'mwsTimeTags',  'mwsTimeQualifs'],
        //     AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, string $format, array $context): string {
        //                 // return "**" . (string)$object . "**";
        //                 return $object ? ($object->getId() ?? "****") : '-****-';
        //             },
        // ]);
        // dd($tagsSerialized);

        [$tags, $tagsGrouped] = $mwsTimeTagRepository->findAllTagsWithCounts();

        return $this->render('@MoonManager/mws_timing/tags.html.twig', [
            'tags' => $tags,
            // 'tagsSerialized' => $tagsSerialized,
            'tagsGrouped' => $tagsGrouped,
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/add/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_add',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagAdd(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-add', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagSlug = $request->request->get('tagSlug');
        $tag = $mwsTimeTagRepository->findOneBy([
            "slug" => $tagSlug,
        ]);
        if (!$tag) {
            throw $this->createNotFoundException("Unknow time tag slug [$tagSlug]");
        }
        $timeSlotId = $request->request->get('timeSlotId');
        $timeSlot = $mwsTimeSlotRepository->findOneBy([
            'id' => $timeSlotId,
        ]);
        if (!$timeSlot) {
            throw $this->createNotFoundException("Unknow time slot id [$timeSlotId]");
        }
        // dd($tag);
        $timeSlot->addTag($tag);

        $this->em->persist($timeSlot);
        $this->em->flush();

        return $this->json([
            'newTags' => $timeSlot->getTags(),
            'sync' => [
                'maxPath' => $timeSlot->getMaxPath(),
                'maxPriceTag' => $timeSlot->getMaxPriceTag(),
            ],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-add')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/update/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_update',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagUpdate(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-update', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagData = $request->request->get('timeTag');
        // $tagData = get_object_vars($tagData);
        // TODO : use serializer deserialize ?
        $tagData = json_decode($tagData, true);
        if (!($tagData['slug'] ?? false)) {
            if ($tagData['label'] ?? false) {
                $slugger = new AsciiSlugger();
                // https://symfony.com/doc/current/components/string.html#slugger
                $tagData['slug'] = $slugger->slug($tagData['label']);
            }
        }
        // dd($tagData);
        $criteria = [
            "slug" => $tagData['slug'] ?? null,
        ];
        // TIPS : For tag update, try ID if exist
        if ($tagData['id'] ?? false) {
            $criteria = [
                "id" => $tagData['id'],
            ];
        }
        $tag = count($criteria)
            ? $mwsTimeTagRepository->findOneBy($criteria)
            : null;
        if (!$tag) {
            $tag = new MwsTimeTag();
        }

        $sync = function ($path) use (&$tag, &$tagData) {
            $set = 'set' . ucfirst($path);
            // $get = 'get' . ucfirst($path);
            // if(!method_exists($tag, $get)) {
            //     $get = 'is' . ucfirst($path);
            // }
            // $v =  $tag->$get();
            $v =  $tagData[$path] ?? null;
            if (null !== $v) {
                $tag->$set($v);
            }
        };

        $sync('slug');
        $sync('label');
        $sync('description');
        if ($tagData['category'] ?? false) {
            $categorySlug = $tagData['category'];
            $category = $mwsTimeTagRepository->findOneBy([
                "slug" => $categorySlug,
            ]);
            $tagData['category'] = $category;
        }
        $sync('category');
        $sync('pricePerHr');
        $pPerRules = &$tagData['pricePerHrRules'];
        foreach ($pPerRules ?? [] as $ruleIdx => &$rule) {
            // dd($rule);
            $pPerRules[$ruleIdx]['price'] = floatval($rule['price'] ?? 0);
            // $rule['maxLimitPriority'] = floatval($rule['maxLimitPriority']);
            $pPerRules[$ruleIdx]['maxLimitPriority'] = floatval($rule['maxLimitPriority'] ?? 0);
        }
        $tag->setPricePerHrRules([]);
        $sync('pricePerHrRules');
        // $tag->addTag($tag);
        // dd($tagData);

        $this->em->persist($tag);
        $this->em->flush();

        return $this->json([
            'updatedTag' => $tag,
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-update')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/remove/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_remove',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagRemove(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-remove', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagSlug = $request->request->get('tagSlug');
        $tag = $mwsTimeTagRepository->findOneBy([
            "slug" => $tagSlug,
        ]);
        if (!$tag) {
            throw $this->createNotFoundException("Unknow time tag slug [$tagSlug]");
        }
        $timeSlotId = $request->request->get('timeSlotId');
        $timeSlot = $mwsTimeSlotRepository->findOneBy([
            'id' => $timeSlotId,
        ]);
        if (!$timeSlot) {
            throw $this->createNotFoundException("Unknow time slot id [$timeSlotId]");
        }
        // dd($tag);

        $timeSlot->removeTag($tag); // TODO : MUST set inverse relation ship ? but no same issue with import ?
        $this->em->persist($timeSlot);
        $this->em->flush();

        return $this->json([
            'newTags' => $timeSlot->getTags(),
            'sync' => [
                'maxPath' => $timeSlot->getMaxPath(),
                'maxPriceTag' => $timeSlot->getMaxPriceTag(),
            ],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-remove')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/remove-all/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_remove_all',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagRemoveAll(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-remove-all', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $timeSlotId = $request->request->get('timeSlotId');
        $timeSlot = $mwsTimeSlotRepository->findOneBy([
            'id' => $timeSlotId,
        ]);
        if (!$timeSlot) {
            throw $this->createNotFoundException("Unknow time slot id [$timeSlotId]");
        }
        // dd($tag);
        $timeSlot->getTags()->clear();
        // Need to re-compute Max for items using this max... 
        // TODO : add event system to plug stuff on max changes ? Doctrine listeners ?
        $timeSlot->setMaxPath(null);
        $timeSlot->getMaxPriceTag(null);

        $this->em->persist($timeSlot);
        $this->em->flush();

        return $this->json([
            'newTags' => $timeSlot->getTags(),
            'sync' => [
                'maxPath' => $timeSlot->getMaxPath(),
                'maxPriceTag' => $timeSlot->getMaxPriceTag(),
            ],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-remove-all')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/delete-and-clean/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_delete_and_clean',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagDeleteAndClean(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-delete-and-clean', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagId = $request->request->get('tagId');
        $tag = $mwsTimeTagRepository->findOneBy([
            "id" => $tagId,
        ]);
        if (!$tag) {
            throw $this->createNotFoundException("Unknow time tag at id [$tagId]");
        }

        $qb = $this->em->createQueryBuilder()
            // ->delete('MoonManagerBundle:MwsUser', 'u'); // Depreciated syntax in 6.3 ? sound to work...         
            ->delete(MwsTimeTag::class, 't')
            ->where('t.id = :tId')
            ->setParameter('tId', $tag->getId());

        // TIPS : no need to cleanup invert relation ship
        // DQL did take care of if...

        // ->select('u')                
        // ->from('App:MwsUser', 'u')                
        // ->where('u.xx = :xx')
        // ->setParameter('xx', $xx);

        $query = $qb->getQuery();
        // dump($query->getSql());
        $resp = $query->execute();
        // dump($resp);

        $this->em->flush();

        return $this->json([
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-delete-and-clean')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/delete-all/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_delete_all',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagDeleteAll(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-delete-all', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $qb = $this->em->createQueryBuilder()
            // ->delete('MoonManagerBundle:MwsUser', 'u'); // Depreciated syntax in 6.3 ? sound to work...         
            ->delete(MwsTimeTag::class, 't');

        $query = $qb->getQuery();
        // dump($query->getSql());
        $resp = $query->execute();
        // dump($resp);

        $this->em->flush();

        return $this->json([
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-delete-all')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/migrate-to/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_migrate_to',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagMigrateTo(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-migrate-to', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $tagFromId = $request->request->get('tagFromId');
        $tagToId = $request->request->get('tagToId');
        $tagFrom = $mwsTimeTagRepository->findOneBy([
            "id" => $tagFromId,
        ]);
        $tagTo = $mwsTimeTagRepository->findOneBy([
            "id" => $tagToId,
        ]);
        if (!$tagFrom) {
            throw $this->createNotFoundException("Unknow time tag from id [$tagFromId]");
        }
        if (!$tagTo) {
            throw $this->createNotFoundException("Unknow time tag from id [$tagToId]");
        }

        foreach ($tagFrom->getMwsTimeTags() as $categoryFrom) {
            $categoryFrom->setCategory($tagTo);
        }
        $this->em->flush();
        foreach ($tagFrom->getMwsTimeSlots() as $tSlot) {
            $tSlot->addTag($tagTo);
        }
        $this->em->flush();
        foreach ($tagFrom->getMwsTimeQualifs() as $tQualif) {
            $tQualif->addTimeTag($tagTo);
        }
        $this->em->flush();

        // Then delete self, will cascade remove with dql :
        $qb = $this->em->createQueryBuilder()
            ->delete(MwsTimeTag::class, 't')
            ->where('t.id = :tId')
            ->setParameter('tId', $tagFrom->getId());
        // TIPS : no need to cleanup invert relation ship
        // DQL did take care of if...
        $query = $qb->getQuery();
        $resp = $query->execute();

        $this->em->flush();

        return $this->json([
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-migrate-to')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/export/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_export',
        methods: ['POST', 'GET'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagExport(
        string|null $viewTemplate,
        Request $request,
        MwsTimeTagRepository $mwsTimeTagRepository,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
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
        // if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-export', $csrf)) {
        //     $this->logger->debug("Fail csrf with", [$csrf]);
        //     throw $this->createAccessDeniedException('CSRF Expired');
        // }

        // $format = $request->request->get('format')
        // ?? $request->query->get('format') ?? 'yaml';
        $format = $request->get('format') ?? 'yaml';

        // $timeSlotId = $request->request->get('timeSlotId');
        $timingLookup = $request->get('timingLookup');
        // dd($timingLookup);

        // $tags = $mwsTimeTagRepository->findAll() ?? [];
        $qb = $mwsTimeTagRepository->createQueryBuilder('t');

        if ($timingLookup) {
            $timingLookup = json_decode($timingLookup, true);
            // dump($timingLookup);
            // $this->logger->debug('Timing lookup', $timingLookup);
            $qb->join('t.mwsTimeSlots', 's');
            $mwsTimeSlotRepository->applyTimingLokup($qb, $timingLookup, 's');
        }

        $tags = $qb->getQuery()->getResult();

        // $allMaxPathsBySlug[$tagSlug]['rules'][$ruleIndex]
        // $allMaxPathsBySlug[$tagSlug]['haveRawPice']
        $allMaxPathsBySlug = $mwsTimeTagRepository->findAllMaxPathsIdxBySlug($timingLookup);

        // dd($allMaxPathsBySlug);

        $tagsSerialized = $this->serializer->serialize(
            $tags,
            $format,
            // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            [
                // ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
                // TODO : filter pricePerHrRules to keep used rules only :
                // TODO : transform class load instead of type ignore ?
                AbstractNormalizer::CALLBACKS => [
                    'pricePerHrRules' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) use ($allMaxPathsBySlug) {
                        // dd($outerObject);
                        $tagSlug = $outerObject?->getSlug() ?? null;
                        // dd($tagSlug);
                        // dd($context);
                        // dump($context['deserialization_path']);
                        // if (is_array($innerObject)) {
                        if ($tagSlug && !($context['deserialization_path'] ?? null)) {
                            $usedRules = [];
                            foreach ($innerObject ?? [] as $ruleIndex => $rule) {
                                if ($allMaxPathsBySlug[$tagSlug]['rules'][$ruleIndex] ?? null) {
                                    $usedRules[] = $rule;
                                }
                            }
                            // dd($usedRules);
                            $innerObject = $usedRules;
                        }
                        return $innerObject;
                    },
                    'pricePerHr' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) use ($allMaxPathsBySlug) {
                        // dump($context['deserialization_path']);
                        // if (is_array($innerObject)) {
                        $tagSlug = $outerObject?->getSlug() ?? null;
                        if (!($context['deserialization_path'] ?? null)) {
                            if (!($allMaxPathsBySlug[$tagSlug]['haveRawPice'] ?? null)) {
                                $innerObject = null;
                            }
                        }
                        return $innerObject;
                    }
                ],
            ],
        );

        $rootPackage = \Composer\InstalledVersions::getRootPackage();
        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];

        $filename = "MoonManager-v" . $packageVersion
            . "-TimeTagsExport-" . time() . ".{$format}"; // . '.pdf';
        if ("monwoo-extractor-export" == $format) {
            $filename = "bulk-answers.json";
        }

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
        '/tag/import/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_tag_import',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function tagImport(
        string|null $viewTemplate,
        Request $request,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-import', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        // format
        $format = $request->get('format');
        $shouldOverwrite = $request->get('shouldOverwrite');
        $shouldRecomputeAllOtherTags = $request->get('shouldRecomputeAllOtherTags');
        // dd($shouldOverwrite);
        // $importFile = $request->request->get('importFile'); // Not txt, will be null
        // dd($importFile);
        // dd ($request->getPayload());
        // dd ($request->getPayload()->get('importFile'));
        // dd( $request->request);

        // https://symfonycasts.com/screencast/symfony-uploads/api-uploads
        // dd($request->files->get('importFile'));
        $importFile = $request->files->get('importFile');
        // dd($request->getContent());
        $importContent = $importFile ? file_get_contents($importFile->getPathname()) : '[]';
        // dd($importContent);

        /** @var MwsTimeTag[] $importTags */
        $importTags = $this->serializer->deserialize(
            $importContent,
            MwsTimeTag::class . "[]",
            $format,
            // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            // [
            //     AbstractNormalizer::IGNORED_ATTRIBUTES => ['id']
            // ],
        );

        // dd($importTags);
        foreach ($importTags as $idx => $importTag) {
            $tag = $mwsTimeTagRepository->findOneBy([
                'slug' => $importTag->getSlug()
            ]);
            if ($tag) {
                if ($shouldOverwrite && $shouldOverwrite != 'null') {
                    // dd($tag->getId());
                    // NOP : setting ID still duplicate on persiste...
                    // $reflectionProperty = new \ReflectionProperty(MwsTimeTag::class, 'id');
                    // $reflectionProperty->setAccessible(true);
                    // $reflectionProperty->setValue($importTag, $tag->getId());
                    // $importTag->setId($tag->getId());
                    $sync = function ($path) use (&$tag, &$importTag) {
                        $set = 'set' . ucfirst($path);
                        $get = 'get' . ucfirst($path);
                        if (!method_exists($tag, $get)) {
                            $get = 'is' . ucfirst($path);
                        }
                        $v =  $importTag->$get();
                        if (null !== $v) {
                            $tag->$set($v);
                        }
                    };
                    $sync('slug');
                    $sync('label');
                    $sync('description');
                    $sync('category');
                    $sync('pricePerHr');
                    $sync('pricePerHrRules');
                    $importTag = $tag;
                } else {
                    continue;
                }
            }

            $this->em->persist($importTag);
            $this->em->flush();
        }
        if ($shouldRecomputeAllOtherTags) {
            $this->forceTimingsPriceRecompute();
            // $importReport .= "Did recompute all timings prices<br/>";
        }

        [$tags, $tagsGrouped] = $mwsTimeTagRepository->findAllTagsWithCounts();
        return $this->json([
            'tags' => $tags,
            'tagsGrouped' => $tagsGrouped,
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-tag-import')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/qualif/toggle/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_qualif_toggle',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function qualifToggle(
        string|null $viewTemplate,
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        MwsTimeTagRepository $mwsTimeTagRepository,
        MwsTimeQualifRepository $mwsTimeQualifRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-qualif-toggle', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $qualifId = $request->request->get('qualifId');
        $qualif = $mwsTimeQualifRepository->findOneBy([
            "id" => $qualifId,
        ]);
        if (!$qualif) {
            throw $this->createNotFoundException("Unknow qualif [$qualifId]");
        }
        $timeSlotId = $request->request->get('timeSlotId');
        $timeSlot = $mwsTimeSlotRepository->findOneBy([
            'id' => $timeSlotId,
        ]);
        if (!$timeSlot) {
            throw $this->createNotFoundException("Unknow time slot id [$timeSlotId]");
        }
        $wasQualified = count(array_intersect(
            $qualif->getTimeTags()->toArray(),
            $timeSlot->getTags()->toArray()
        )) == count($qualif->getTimeTags()->toArray());

        foreach ($mwsTimeQualifRepository->findAll() as $allQualif) {
            // Clean all existing tags (toggle)
            foreach ($allQualif->getTimeTags() as $tag) {
                $timeSlot->removeTag($tag);
            }
        }

        // TODO : cleanOnToggle feature is buggy
        //        behavior on multi-select (re-select self
        //        and try to merge with other tag will remove
        //        self config, not what we wanted...)
        //         + Config from user param if found way to do it
        $cleanOnToggle = false;

        if (!$cleanOnToggle || !$wasQualified) {
            // Add tag if was not present, keep clean otherwise
            foreach ($qualif->getTimeTags() as $tag) {
                $timeSlot->addTag($tag);
            }
        }

        $this->em->persist($timeSlot);
        $this->em->flush();

        return $this->json([
            // TODO : refactor 'newTags', put in 'sync' system as 'tags' instead
            'newTags' => $timeSlot->getTags(),
            'sync' => [
                'maxPath' => $timeSlot->getMaxPath(),
                'maxPriceTag' => $timeSlot->getMaxPriceTag(),
            ],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-qualif-toggle')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/qualif/sync/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_qualif_sync',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function qualifSync(
        string|null $viewTemplate,
        Request $request,
        MwsTimeTagRepository $mwsTimeTagRepository,
        MwsTimeQualifRepository $mwsTimeQualifRepository,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-qualif-sync', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $qualifInput = $request->request->get('qualif');
        // TODO : use serializer deserialize ?
        $qualifInput = json_decode($qualifInput, true);
        // dd($qualifInput);

        $criteria = [
            "id" => $qualifInput['id'],
        ];
        $qualif = count($criteria)
            ? $mwsTimeQualifRepository->findOneBy($criteria)
            : null;
        if (!$qualif) {
            $qualif = new MwsTimeQualif();
        }
        if ($qualifInput['_shouldDelete'] ?? false) {
            $this->em->remove($qualif);
            $this->em->flush();
            return $this->json([
                'sync' => null,
                'didDelete' => true,
                'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-qualif-sync')->getValue(),
                'viewTemplate' => $viewTemplate,
            ]);
        }
        $sync = function ($path) use (&$qualif, &$qualifInput) {
            $get = 'get' . ucfirst($path);
            $v =  $qualifInput[$path] ?? null;
            if (null !== $v) {
                $set = 'set' . ucfirst($path);
                if (!method_exists($qualif, $set)) {
                    // Is collection :
                    $add = 'add' . ucfirst($path);
                    if (!method_exists($qualif, $add)) {
                        $add = preg_replace('/s$/', '', $add);
                    }
                    $collection = $qualif->$get();
                    $collection->clear();
                    foreach ($v as $subV) {
                        $qualif->$add($subV);
                    }
                } else {
                    $qualif->$set($v);
                }
            }
        };

        $sync('label');
        if (is_array($qualifInput['primaryColorRgb'] ?? false)) {
            $qualifInput['primaryColorRgb'] = implode(
                ', ',
                $qualifInput['primaryColorRgb']
            );
        }
        $sync('primaryColorHex');
        $sync('primaryColorRgb');
        if ($qualifInput['timeTags'] ?? false) {
            foreach ($qualifInput['timeTags'] as $idx => $tagInput) {
                $tag = $mwsTimeTagRepository->findOneBy([
                    "slug" => $tagInput['slug'] ?? null,
                ]);
                // TODO : what if not found ? null value ok or create tag ?
                $qualifInput['timeTags'][$idx] = $tag;
            }
            array_multisort(
                // array_keys($allTagsList),
                array_map(function ($t) {
                    return $t->getLabel();
                }, $qualifInput['timeTags']),
                SORT_NATURAL | SORT_FLAG_CASE,
                $qualifInput['timeTags']
            );
        }
        $sync('timeTags');
        $sync('shortcut');
        // TODO : $sync('quickUserHistory');

        // TODO : sync from Doctrine model with typed extractor instead of adding all model
        //         change by hand in this king of files ?
        $sync('htmlIcon');

        // dd($qualif);

        $this->em->persist($qualif);
        $this->em->flush();

        return $this->json([
            'sync' => [
                'id' => $qualif->getId(),
                'timeTags' => $qualif->getTimeTags(),
                // TODO : $qualif export serialization ? (Sync group ?)
                // 'maxPath' => $timeSlot->getMaxPath(),
                // 'maxPriceTag' => $timeSlot->getMaxPriceTag(),
            ],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-qualif-sync')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/qualif/config-sync/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_qualif_config_sync',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function qualifConfigSync(
        string|null $viewTemplate,
        Request $request,
        MwsTimeTagRepository $mwsTimeTagRepository,
        MwsTimeQualifRepository $mwsTimeQualifRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        /** @var MwsUser $user */
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth", []);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-timing-qualif-config-sync', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $config = $request->request->get('config');
        $config = json_decode($config, true);

        // dd($config);
        $quickQualif = &$config['timing']['quickQualif'] ?? null;
        $quickQualifList = &$quickQualif['list'] ?? null;

        $quickQualifList = array_filter($quickQualifList, function (
            $qualifLabel
        ) use ($mwsTimeQualifRepository) {
            return $mwsTimeQualifRepository->findOneBy([
                "label" => $qualifLabel,
            ]);
        });

        $mergedConfig = array_merge($user->getConfig() ?? [], $config ?? []);

        $user->setConfig($mergedConfig);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'sync' => [...$config],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-qualif-config-sync')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/tag/import/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_qualif_import',
        methods: ['POST'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function qualifImport(
        string|null $viewTemplate,
        Request $request,
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
        if (!$this->isCsrfTokenValid('mws-csrf-timing-qualif-import', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        // format
        $format = $request->get('format');
        $shouldOverwrite = $request->get('shouldOverwrite');
        $shouldRecomputeAllOtherTags = $request->get('shouldRecomputeAllOtherTags');

        // // dd($shouldOverwrite);
        // // $importFile = $request->request->get('importFile'); // Not txt, will be null
        // // dd($importFile);
        // // dd ($request->getPayload());
        // // dd ($request->getPayload()->get('importFile'));
        // // dd( $request->request);
        // // https://symfonycasts.com/screencast/symfony-uploads/api-uploads
        // // dd($request->files->get('importFile'));
        // $importFile = $request->files->get('importFile');
        // // dd($request->getContent());
        // $importContent = $importFile ? file_get_contents($importFile->getPathname()) : '[]';
        // // dd($importContent);
        // /** @var MwsTimeTag[] $importTags */
        // $importTags = $this->serializer->deserialize(
        //     $importContent,
        //     MwsTimeTag::class . "[]",
        //     $format,
        //     // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
        //     // [
        //     //     AbstractNormalizer::IGNORED_ATTRIBUTES => ['id']
        //     // ],
        // );
        // // dd($importTags);
        // foreach ($importTags as $idx => $importTag) {
        //     $tag = $mwsTimeTagRepository->findOneBy([
        //         'slug' => $importTag->getSlug()
        //     ]);
        //     if ($tag) {
        //         if ($shouldOverwrite && $shouldOverwrite != 'null') {
        //             // dd($tag->getId());
        //             // NOP : setting ID still duplicate on persiste...
        //             // $reflectionProperty = new \ReflectionProperty(MwsTimeTag::class, 'id');
        //             // $reflectionProperty->setAccessible(true);
        //             // $reflectionProperty->setValue($importTag, $tag->getId());
        //             // $importTag->setId($tag->getId());
        //             $sync = function ($path) use (&$tag, &$importTag) {
        //                 $set = 'set' . ucfirst($path);
        //                 $get = 'get' . ucfirst($path);
        //                 if (!method_exists($tag, $get)) {
        //                     $get = 'is' . ucfirst($path);
        //                 }
        //                 $v =  $importTag->$get();
        //                 if (null !== $v) {
        //                     $tag->$set($v);
        //                 }
        //             };
        //             $sync('slug');
        //             $sync('label');
        //             $sync('description');
        //             $sync('category');
        //             $sync('pricePerHr');
        //             $sync('pricePerHrRules');
        //             $importTag = $tag;
        //         } else {
        //             continue;
        //         }
        //     }
        //     $this->em->persist($importTag);
        //     $this->em->flush();
        // }
        // if ($shouldRecomputeAllOtherTags) {
        //     $this->forceTimingsPriceRecompute();
        //     // $importReport .= "Did recompute all timings prices<br/>";
        // }
        // [$tags, $tagsGrouped] = $mwsTimeTagRepository->findAllTagsWithCounts();

        return $this->json([
            // 'qualifs' => $qualifs,
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-timing-qualif-import')->getValue(),
            'viewTemplate' => $viewTemplate,
        ]);
    }

    #[Route(
        '/qualif/export/{viewTemplate<[^/]*>?}',
        name: 'mws_timing_qualif_export',
        methods: ['POST', 'GET'],
        defaults: [
            'viewTemplate' => null,
        ],
    )]
    public function qualifExport(
        string|null $viewTemplate,
        Request $request,
        MwsTimeQualifRepository $mwsTimeQualifRepository,
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
        // if (!$this->isCsrfTokenValid('mws-csrf-timing-tag-export', $csrf)) {
        //     $this->logger->debug("Fail csrf with", [$csrf]);
        //     throw $this->createAccessDeniedException('CSRF Expired');
        // }

        $format = $request->get('format') ?? 'yaml';

        // $timingLookup = $request->get('timingLookup');
        // // dd($timingLookup);

        $qb = $mwsTimeQualifRepository->createQueryBuilder('q');

        // if ($timingLookup) {
        //     $timingLookup = json_decode($timingLookup, true);
        //     // dump($timingLookup);
        //     // $this->logger->debug('Timing lookup', $timingLookup);
        //     $qb->join('t.mwsTimeSlots', 's');
        //     $mwsTimeSlotRepository->applyTimingLokup($qb, $timingLookup, 's');
        // }

        $qualifs = $qb->getQuery()->getResult();

        // TODO : order used by user history at TOP, in user history orders ? +
        //        only export user history limits or always all ? (add info about
        //        existence in user history for re-import to work ?)

        $qualifsSerialized = $this->serializer->serialize(
            $qualifs,
            $format,
            // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            [
                // ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    'id', 'quickUserHistory'
                ],
                AbstractNormalizer::CALLBACKS => [
                    'timeTags' => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) {
                        if ($innerObject && !($context['deserialization_path'] ?? null)) {
                            $norm = array_map(
                                function (MwsTimeTag $o) {
                                    // return $o->getSlug();
                                    return ['slug'  => $o?->getSlug()] ?? null;
                                },
                                $innerObject->toArray() ?? []
                            );
                            sort($norm);
                            $innerObject = $norm;
                        }
                        return $innerObject;
                    },
                ],
            ],
        );

        $rootPackage = \Composer\InstalledVersions::getRootPackage();
        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];

        $filename = "MoonManager-v" . $packageVersion
            . "-TimeQualifsExport-" . time() . ".{$format}"; // . '.pdf';

        $response = new Response();
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

        $response->setContent($qualifsSerialized);
        return $response;
    }
}
