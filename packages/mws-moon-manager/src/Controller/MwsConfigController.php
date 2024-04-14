<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MWS\MoonManagerBundle\Entity\MwsMessageTchatUpload;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Repository\MwsMessageTchatUploadRepository;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
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
        protected KernelInterface $kernel,
        protected LoggerInterface $logger,
        protected EntityManagerInterface $em,
        protected ParameterBagInterface $params,
        protected SluggerInterface $slugger,
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
        $projectDir = $this->params->get('kernel.project_dir');
        $backupsDir = "$projectDir/bckup";
        $uploadSubFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
        $uploadSrc = "$projectDir/$uploadSubFolder/messages/tchats";
        $uploadsTotalSize = $this->humanSize(
            $uSize = $this->mwsFileSize($uploadSrc)
        );
        $dbSrc = "$projectDir/var/data.db.sqlite";
        $databasesTotalSize = $this->humanSize(
            $dSize = $this->mwsFileSize($dbSrc)
        );
        $backupTotalSize = $this->humanSize($uSize + $dSize);

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
                // dd($surveyAnswers);
                // TODO : custom other path than tchat message upload folder ?
                $uploadFiles = $surveyAnswers['uploadFile'] ?? null; // TODO : refactor for multiples files ?
                if ($uploadFiles && count($uploadFiles)) {
                    $filesystem = new Filesystem();
                    $bckupFile = $uploadFiles[0];
                    $bckupName = implode('.', array_slice(explode(
                        '.', $bckupFile['name']
                    ),0,-1));
                    $bckupPath = "$uploadSrc/{$bckupFile['name']}";
                    // TODO : config behavior ? alway bckup for now
                    $this->backupInternalSave();
                    // dd($bckupPath);
                    $isSqlite = ends_with($bckupPath, '.sqlite');
                    if ($isSqlite) {
                        try {
                            $filesystem->copy($bckupPath, $dbSrc, true);
                            $backupForm->addError(new FormError(
                                "Backup SQLITE OK, recharger la page pour vérifier les backups automatiques."
                            ));
                        } catch (Exception $e) {
                            // handle exception
                            $this->logger->error(
                                "DB error " . $e->getMessage()
                            );
                            $backupForm->addError(new FormError(
                                "DB error " . $e->getMessage()
                            ));
                        }
                    } else {
                        $zipFile = new ZipFile();
                        try {
                            // ->extractTo($outputDirExtract) // extract files to the specified directory
                            $zipFile->openFile($bckupPath)
                            ->deleteFromRegex('~^\.~') // delete all hidden (Unix) files
                            ->deleteFromRegex('~\.\.~') // delete all relatives path
                            ;
                            $dbTestPath = "$bckupName/data.db.sqlite";
                            // var_dump($dbTestPath);
                            $files = $zipFile->getListFiles();
                            if (!count(array_filter($files, function ($f)
                            use ($dbTestPath) {
                                return $f === $dbTestPath;
                            }))) {
                                throw new ZipException('"/data.db.sqlite" is mandatory inside .zip');
                            }
                            // var_dump($files);exit();
                            if(!count(array_filter($files, function ($f)
                            use ($dbTestPath, $bckupName) {
                                return $f === $dbTestPath
                                || starts_with($f, "$bckupName/messages/tchats/");
                            }))) {
                                throw new ZipException('Only "/messages/tchats/" extra folder is allowed');
                            }

                            // If zip have /messages/tchats, cleanup before extract
                            if($zipFile->count() > 1 ) {
                                // TODO : configurable option for it ?
                                $filesystem->remove($uploadSrc);
                                $backupForm->addError(new FormError(
                                    "Backup Zip Did cleanup old uploads."
                                ));
                                }
                            $extractDest = "$projectDir/$uploadSubFolder";
                            $zipFile->extractTo($extractDest); // extract files to the specified directory
                            $filesystem->rename(
                                "$projectDir/$uploadSubFolder/$bckupName/data.db.sqlite",
                                $dbSrc,
                                true
                            );

                            // Clean up extracted folder :
                            $filesystem->remove($extractDest);
                            $backupForm->addError(new FormError(
                                "Backup Zip OK, recharger la page pour vérifier les backups automatiques."
                            ));
                        } catch (ZipException $e) {
                            // handle exception
                            $this->logger->error(
                                "Backup Zip error " . $e->getMessage()
                            );
                            $backupForm->addError(new FormError(
                                "Backup Zip error " . $e->getMessage()
                            ));
                        } finally {
                            $zipFile->close();
                        }
                    }
                    $filesystem->remove($bckupPath);
                }
                // $this->backupImport($blob, $type);

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

        $rootPackage = \Composer\InstalledVersions::getRootPackage();
        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];

        $backupName = $request->get('backupName', null);
        // $backupName = trim($this->slugger->slug($backupName, '-'));
        $backupName = $this->slugger->slug($backupName, '-');
        $backupName = strlen($backupName) ? "-$backupName" : '';

        $this->logger->debug("Will backup $backupName");
        $shouldBackup = $request->get('shouldBackup', true);
        $shouldBackup && $this->backupInternalSave($backupName);

        $respData = null;
        $contentType = 'application/vnd.sqlite3';

        // TODO : phar : extract all site to one php phar for efficiency ?
        // db|db-zip|light (|full not decided yet...)
        $backupType = $request->get('backupType', null);
        $shouldZip = "db-zip" === $backupType; // TODO ? : $request->get('shouldZip', true);
        // TIPS : no If case for 'db' or any other type => do lowest if unknow type
        $isLightOrFull = false !== array_search($backupType, [
            'light', 'full'
        ]);
        // TODO ? Full is may be too much since will send private keys etc...
        //       => bette use ftp and keep this feature outside of common usage ?
        $isFull = 'full' === $backupType;

        $projectDir = $this->params->get('kernel.project_dir');
        $dbSrc = "$projectDir/var/data.db.sqlite";

        $filename = "data$backupName.$packageVersion." . time() . ".sqlite";
        $zipName = "backup$backupName.$packageVersion." . time();
        $zipFilename = "$zipName.zip";

        $respData = file_get_contents($dbSrc);

        $zipDirSources = [];
        $zipSources = [
            $dbSrc => "$zipName/" . basename($dbSrc),
        ];
        if ($isLightOrFull) {
            $shouldZip = true;
            $uploadSubFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
            $uploadSrc = "$projectDir/$uploadSubFolder/messages/tchats";
            $zipDirSources[$uploadSrc] = "$zipName/messages/tchats";
        }

        if ($isFull) {
        }

        if ($shouldZip) {
            // https://packagist.org/packages/nelexa/zip
            // $zipFile = new \PhpZip\ZipFile();
            // $zipFile
            // ->addFromString('zip/entry/filename', 'Is file content') // add an entry from the string
            // ->addFile('/path/to/file', 'data/tofile') // add an entry from the file
            // ->addDir(__DIR__, 'to/path/') // add files from the directory
            // ->saveAsFile($outputFilename) // save the archive to a file
            // ->close(); // close archive                
            // // open archive, extract, add files, set password and output to browser.
            // $zipFile
            // ->openFile($outputFilename) // open archive from file
            // ->extractTo($outputDirExtract) // extract files to the specified directory
            // ->deleteFromRegex('~^\.~') // delete all hidden (Unix) files
            // ->addFromString('dir/file.txt', 'Test file') // add a new entry from the string
            // ->setPassword('password') // set password for all entries
            // ->outputAsAttachment('library.jar'); // output to the browser without saving to a file

            // TODO : secu test case : unzip archive with forced '../../' path ?

            $zipFile = new ZipFile();
            try {
                // var_dump($zipSources);exit();
                // $zipFile
                // ->addFile('/path/to/file', 'data/tofile') // add an entry from the file
                // ->addDir(__DIR__, 'to/path/') // add files from the directory
                // ->deleteFromRegex('~^\.~') // delete all hidden (Unix) files
                // ->setPassword('password') // set password for all entries
                foreach ($zipDirSources as $dirSrc => $dirDst) {
                    $zipFile->addDir($dirSrc, $dirDst);
                }
                foreach ($zipSources as $fileSrc => $fileDst) {
                    $zipFile->addFile($fileSrc, $fileDst);
                }

                $zipFile
                    ->outputAsAttachment($zipFilename); // output to the browser without saving to a file
                // TIPS : noting will run after exit of previous call...
                dd('Strange, this code should not run...');
            } catch (ZipException $e) {
                // handle exception
                $this->logger->error(
                    "Backup Zip error " . $e->getMessage()
                );
            } finally {
                $zipFile->close();
            }
        }

        $response = new Response($respData);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-chache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
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
        $uploadSubFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
        $pathRaw = "$projectDir/$uploadSubFolder/$mediaPath";
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

    protected function backupInternalSave($backupName = null)
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $args = [
            'command' => 'mws:backup',
        ];
        if ($backupName && strlen($backupName)) {
            $args['backupName'] = $backupName;
        }
        $input = new ArrayInput($args);
        $output = new NullOutput();
        $application->run($input, $output);
    }
}
