<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use MWS\MoonManagerBundle\Entity\MwsMessageTchatUpload;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Repository\MwsMessageTchatUploadRepository;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Vich\UploaderBundle\Metadata\MetadataReader;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route(
    '/{_locale<%app.supported_locales%>}/mws-config',
    options: ['expose' => true],
)]
#[SecuAttr(
    "is_granted('" . MwsUser::ROLE_ADMIN . "')",
    // MwsUser::IS_GRANTED_ROLE_ADMIN.'',
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsConfigController extends AbstractController
{
    public function __construct(
        protected Security $security,
        protected LoggerInterface $logger,
        protected EntityManagerInterface $em,
        protected ParameterBagInterface $params,
        // protected SluggerInterface $slugger,
    ) {
    }

    #[Route('/backup', name: 'mws_config_backup')]
    public function backup(
        Request $request,
        MwsTimeSlotRepository $mwsTimeSlotRepository,
        // PropertyMappingFactory $factory,
        // MetadataReader $uploaderMetadata,
        // ContainerConfigurator $containerConfigurator, // NOP, not from controller...
        // ParameterBagInterface $params,
        UploaderHelper $uploadHelper,
        MwsMessageTchatUploadRepository $mwsMessageTchatUploadRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        // https://symfony.com/doc/current/service_container.html#remove-services
        // $services = $containerConfigurator->services();
        // // $services->remove(RemovedService::class);
        // dd($services->get('vich_uploader.metadata_reader'));
        // dd($this->getSubscribedServices());
        // dd($params->get('vich_uploader'));
        // TIPS : php bin/console debug:container
        // dd($this->container->get('vich_uploader.metadata_reader'));
        // dd($this->container->get('vich_uploader.metadata_reader'));
        // https://github.com/dustin10/VichUploaderBundle/issues/1075

        // $image = $mwsMessageTchatUploadRepository->findOneBy([]);
        // // dd($image);
        // dd($uploadHelper->asset($image , 'mediaFile', MwsMessageTchatUpload::class));
        // https://github.com/dustin10/VichUploaderBundle/issues/1075
        // $urlPrefix = uri_prefix
        $uploadUriPrefix = $uploadHelper->asset([
            'mediaName' => ' ',
            'mediaFile' => [
                'filename' => ' ',
                'basename' => ' ',
            ]
        ], 'mediaFile', MwsMessageTchatUpload::class);
        // $uploadUriPrefix = str_replace('/ ', '', $uploadUriPrefix);
        $uploadUriPrefix = str_replace(' ', '', $uploadUriPrefix);
        // dd($uploadUriPrefix);

        // $csrf = $request->request->get('_csrf_token');
        // if (!$this->isCsrfTokenValid('mws-csrf-config-backup', $csrf)) {
        //     $this->logger->debug("Fail csrf with", [$csrf, $request]);
        //     throw $this->createAccessDeniedException('CSRF Expired');
        // } // Classic SF form already have CSRF Validations....

        $lastBackup = [
            // TIPS urlencode() will use '+' to replace ' ', rawurlencode is RFC one
            "jsonResult" => rawurlencode(json_encode([
                // Survey Js Data init
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsConfigBackupType.json.twig",
                [
                    // Twig Template data
                ]
            )),
        ];
        $backupForm = $this->createForm(MwsSurveyJsType::class, $lastBackup);
        $backupForm->handleRequest($request);

        if ($backupForm->isSubmitted()) {
            $this->logger->debug("Did submit backup form");

            if ($backupForm->isValid()) {
                $this->logger->debug("Config bckup form ok");

                // dd($backupForm->get('surveyJsModel'));
                // dd($backupForm);
                $surveyAnswers = json_decode(
                    urldecode($backupForm->get('jsonResult')->getData()),
                    true
                );
                // TODO : allow bckup comment => will be 'SLUG' add
                //        to end of backup folder, allowing to add
                //        text info about timed backup
                //        (zip name will use folder name...)
                if ($surveyAnswers['MwsConfigBackupType'] ?? false) {
                    // $keyword = $surveyAnswers['searchKeyword'] ?? null;
                    return $this->redirectToRoute(
                        'mws_timings_report',
                        array_merge($request->query->all(), [
                            "page" => 1,
                        ]),
                        Response::HTTP_SEE_OTHER
                    );
                }
            }
        }

        $projectDir = $this->params->get('kernel.project_dir');
        $backupsDir = "$projectDir/bckup";
        $finder = [];
        if (!empty(glob($backupsDir))) {
            $finder = new Finder();
            $finder->directories()->in($backupsDir)
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs()
                ->depth(0);
            // ->exclude( $exclude )
            // ->notPath('#(^|/)_.+(/|$)#') // Ignore path start with underscore (_).
            // ->notPath( '/.*\/node_modules\/.*/' );
            // $finder->copy("$projectDir/");
            // $uploadSrc = $this->params->get('vich_uploader.mappings.message_tchats_upload.upload_destination');
            // $uploadSrc = $this->params->get('vich_uploader');
            // ->sort(function ($a, $b) {
            //     $aNumber = intval(explode(".", self::end(explode('-', $a->getRealpath())))[0]);
            //     $bNumber = intval(explode(".", self::end(explode('-', $b->getRealpath())))[0]);
            //     return $aNumber - $bNumber;
            //     // return strcmp($b->getRealpath(), $a->getRealpath());
            // });

            $finder->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                // return strcmp($a->getRealPath(), $b->getRealPath());
                return strcmp($b->getRealPath(), $a->getRealPath());
            });
        }
        // $nbFiles = iterator_count($finder);
        // $maxFileIndex = iterator_count($finder) - 1;
        // dd(iterator_to_array($finder, false));
        $bFiles = array_map(function (SplFileInfo $f) {
            return $f->getRelativePathname() . ' ['
                . $this->humanSize(
                    $this->mwsFileSize($f->getPathname())
                ) . ']';
        }, iterator_to_array($finder, false));
        // dd($bFiles);

        $backupsTotalSize = $this->humanSize(
            $this->mwsFileSize($backupsDir)
        );

        $subFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
        $uploadSrc = "$projectDir/$subFolder/messages/tchats";
        $uploadsTotalSize = $this->humanSize(
            $uSize = $this->mwsFileSize($uploadSrc)
        );
        $dbSrc = "$projectDir/var/data.db.sqlite";
        $databasesTotalSize = $this->humanSize(
            $dSize = $this->mwsFileSize($dbSrc)
        );
        $backupTotalSize = $this->humanSize($uSize + $dSize);

        $finder = [];
        if (!empty(glob($uploadSrc))) {
            $finder = new Finder();
            $finder->files()->in($uploadSrc)
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs();
            $finder->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                return strcmp($b->getRealPath(), $a->getRealPath());
            });    
        }

        $uploadedFiles = array_map(function (SplFileInfo $f) use ($uploadUriPrefix) {
            return $uploadUriPrefix . $f->getRelativePathname() . ' ['
                . $this->humanSize(
                    $this->mwsFileSize($f->getPathname())
                ) . ']';
        }, iterator_to_array($finder, false));

        $qb = $mwsTimeSlotRepository->createQueryBuilder('s')
        ->select('count(s.id)')
        ->where('s.thumbnailJpeg IS NOT NULL');

        $thumbnailsCount = $qb->getQuery()->getSingleScalarResult();
        // dd($qb->getQuery()->getDQL());
        // dd($thumbnailsCount);

        return $this->render('@MoonManager/mws_config/backup.html.twig', [
            'backupForm' => $backupForm,
            'backups' => $bFiles,
            'configState' => [
                'backupsTotalSize' => $backupsTotalSize,
                'uploadsTotalSize' => $uploadsTotalSize,    
                'databasesTotalSize' => $databasesTotalSize,
                'backupTotalSize' => $backupTotalSize,
                'uploadedFiles' => $uploadedFiles,
                'thumbnailsCount' => $thumbnailsCount,
            ]
        ]);
    }

    // In Bytes
    protected function mwsFileSize($path)
    {
        // https://gist.github.com/eusonlito/5099936
        $size = 0;
        if (is_file($path)) {
            return filesize($path);
        }

        foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->mwsFileSize($each);
        }

        return $size;
    }

    // TODO : remove code factorization with twig filters...
    protected function humanSize($size)
    {
        // Then, humanize :
        if ($size < 1024) {
            $size = $size . " Bytes";
        } elseif (($size < 1048576) && ($size > 1023)) {
            $size = round($size / 1024, 1) . " KB";
        } elseif (($size < 1073741824) && ($size > 1048575)) {
            $size = round($size / 1048576, 1) . " MB";
        } else {
            $size = round($size / 1073741824, 1) . " GB";
        }
        return $size;
    }


    public const defaultThumbSize = 100;
    #[Route(
        '/backup/download',
        methods: ['POST'],
        name: 'mws_config_backup_download'
    )]
    public function backupDownload(
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $backupName = $request->get('backupName', null);

        $this->logger->debug("Will backup $backupName");
        $respData = null;

        // TODO : light db backup vs zip full backup

        $response = new Response($respData);
        // $response->headers->set('Content-Type', 'image/jpg');
        return $response;
    }

    #[Route(
        '/uploads/{mediaPath<.*$>?}',
        methods: ['GET'],
        name: 'mws_config_upload_proxy'
    )]
    public function uploadProxy(
        $mediaPath,
        Request $request,
    ): Response {
        $user = $this->getUser();
        // dd($mediaPath);
        // if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
        //     throw $this->createAccessDeniedException('Only for admins');
        // }
        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $format = strtolower(array_slice(explode('.', $mediaPath), -1)[0] ?? '');

        $projectDir = $this->getParameter('kernel.project_dir');
        // TODO : no property accessors ?? mws_moon_manager.uploadSubFolder
        // $projectDir = $this->getParameter('mws_moon_manager.uploadSubFolder');
        $subFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
        $pathRaw = "$projectDir/$subFolder/$mediaPath";
        // dump($pathRaw);
        $path = realpath($pathRaw);
        // dd($path);
        if (!$path || !file_exists($path)) {
            // Please, check apps/mws-sf-pdf-billings/backend/config/packages/mws_moon_manager.yaml:mws_moon_manager.uploadSubFolder etc...
            throw $this->createNotFoundException("Media path $pathRaw not found");
        }
        $projPath = realpath($projectDir);
        if (!starts_with($path, $projPath)) {
            // dd('// TODO TESTs : Do not allow other folders like "../../" ? Browser check it, testable ?');
            // Please, check apps/mws-sf-pdf-billings/backend/config/packages/mws_moon_manager.yaml:mws_moon_manager.uploadSubFolder etc...
            throw $this->createNotFoundException("Media path $pathRaw not found");
        }
        // dd('ok');
        $respData = file_get_contents($path);
        $response = new Response($respData);
        // https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
        $mime = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpge' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'csv' => 'text/comma-separated-values',
            'xml' => 'application/xml',
            'yaml' => 'application/yaml',
        ][$format] ?? 'text/plain';
        if ($mime) {
            $response->headers->set('Content-Type', $mime);
            // dd($mime);
        }

        return $response;
    }
}
