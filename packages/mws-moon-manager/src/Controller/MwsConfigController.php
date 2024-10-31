<?php
// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use MWS\MoonManagerBundle\Entity\MwsMessageTchatUpload;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Repository\MwsMessageTchatUploadRepository;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use PHPUnit\Util\Json;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use SQLite3;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Metadata\MetadataReader;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route(
    '/{_locale<%app.supported_locales%>}/mws-config',
    options: ['expose' => true],
)]
// #[SecuAttr(
//     "false",
//     // "is_granted('" . MwsUser::ROLE_ADMIN . "') and app.request.attributes.get('_route')",
//     // MwsUser::IS_GRANTED_ROLE_ADMIN.'',
//     statusCode: 401,
//     message: MwsLoginFormAuthenticator::t_failToGrantAccess
// )]
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
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
    )]
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
        $uploadSrc = "$projectDir/$uploadSubFolder";
        $uploadTchatSrc = "$projectDir/$uploadSubFolder/messages/tchats";
        $dbSrc = "$projectDir/var/data.db.sqlite";
        $gdprDbSrc = "$projectDir/var/data.gdpr-ok.db.sqlite";
        $gdprUploadSrc = "$projectDir/var/uploads-gdpr-ok";
        // $extractDest = "$uploadSrc";
        $extractDest = "$projectDir/var/cache";

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
                    $bckupNameFromFile = implode('.', array_slice(explode(
                        '.', $bckupFile['name']
                    ),0,-1));
                    $rawUploadPath = "$uploadSrc/messages/tchats/{$bckupFile['name']}";
                    $bckupPath = "$projectDir/var/cache/{$bckupFile['name']}";
                    // Keep it OUTSIDE of next backup call :
                    $filesystem->rename($rawUploadPath, $bckupPath, true);
                    // TODO : config behavior ? alway bckup for now
                    $this->backupInternalSave();
                    // TODO : better configure zip to another upload folder than same
                    // as the backuped ones ?
                    // $filesystem->rename("$projectDir/var/cache/tmp.zip", $bckupPath, true);
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
                            // var_dump($dbTestPath);
                            $files = $zipFile->getListFiles();
                            $zipBckupName = explode(DIRECTORY_SEPARATOR, $files[0])[0] ?? '';
                            $dbTestPath = "$zipBckupName/data.db.sqlite";
                            // var_dump($zipBckupName); var_dump($files); exit();
                            if (!count(array_filter($files, function ($f)
                            use ($dbTestPath) {
                                return $f === $dbTestPath;
                            }))) {
                                throw new ZipException('"/data.db.sqlite" is mandatory inside .zip');
                            }
                            // var_dump($files);exit();
                            if(!count(array_filter($files, function ($f)
                            use ($dbTestPath, $zipBckupName) {
                                return $f === $dbTestPath
                                || starts_with($f, "$zipBckupName/messages/tchats/")
                                || starts_with($f, "$zipBckupName/timings/thumbs/"); // TODO : factorize, centralize, configurable... ?
                            }))) {
                                throw new ZipException('Only "/messages/tchats/" or "/timings/thumbs/" extra folder is allowed');
                            }

                            $zipFile->extractTo($extractDest); // extract files to the specified directory

                            // If zip have /messages/tchats, cleanup before extract
                            $haveMediaFiles = $zipFile->count() > 1;
                            if($haveMediaFiles) {
                                // TODO : configurable option for it ?
                                $filesystem->remove($uploadSrc);
                                $backupForm->addError(new FormError(
                                    "Backup Zip Did cleanup old uploads."
                                ));
                            }

                            // Next db rewrite will trigger error :
                            // sqlite General error: 8 attempt to write a readonly database when replacing db
                            // https://stackoverflow.com/questions/3319112/sqlite-error-attempt-to-write-a-readonly-database-during-insert
                            // // TIPS : flush before rename :
                            // $this->em->flush();
                            // // var_dump(scandir("$projectDir/var/")); exit();
                            // // $this->em->close(); // TIPS, can't close, will break next calls
                            // // But still error, keep the old db ref running :
                            // $filesystem->remove("$dbSrc.bckup");
                            // $filesystem->rename(
                            //     $dbSrc,
                            //     "$dbSrc.bckup",
                            //     true
                            // );
                            // TODO : RESET EntityManager connection,
                            //        persist and flush are broken after db rename...
                            $filesystem->rename(
                                "$extractDest/$zipBckupName/data.db.sqlite",
                                $dbSrc,
                                true
                            );
                            if($haveMediaFiles) {
                                // TODO : to heavy secu ? copy folder instead of parts by parts needing code update to load new backup style ?
                                $tchatMedias = "$extractDest/$zipBckupName/messages/tchats";
                                if (file_exists($tchatMedias)) {
                                    $filesystem->rename(
                                        "$tchatMedias",
                                        "$uploadSrc/messages/tchats",
                                        true
                                    );                                        
                                }
                                $timingMedias = "$extractDest/$zipBckupName/timings/thumbs";
                                // dd($timingMedias);
                                if (file_exists($timingMedias)) {
                                    $filesystem->rename(
                                        $timingMedias,
                                        "$uploadSrc/timings/thumbs",
                                        true
                                    );
                                }
                            }
                            // $bulkload_connection = new SQLite3("$dbSrc");

                            // Clean up extracted folder :
                            $filesystem->remove("$extractDest/$zipBckupName");
                            // $backupForm->addError(new FormError(
                            //     "Backup Zip OK, recharger la page pour vérifier les backups automatiques."
                            // )); // No need anymore, have force reload client side

                            // TODO : ensure DB is at top version by migrating on new db ?
                            //       => but on next request to keep timings ?

                            // TODO run : php bin/console doctrine:migrations:migrate -n
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
                    // TODO : option to keep history or not of uploaded bckup ?
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

        // TIPS : compute size AFTER bck up operation to get right last size.
        $uploadsTotalSize = $this->humanSize(
            $uSize = $this->mwsFileSize($uploadSrc)
        );
        $uploadsTchatTotalSize = $this->humanSize(
            $uTchatSize = $this->mwsFileSize($uploadTchatSrc)
        );
        
        $databasesTotalSize = $this->humanSize(
            $dSize = $this->mwsFileSize($dbSrc)
        );
        $gdprBackupSize = $this->humanSize(
            $gSize = $this->mwsFileSize($gdprDbSrc)
            + $this->mwsFileSize($gdprUploadSrc)
        );
        // Tips : not counting  + $gSize in total since
        //        is more like an idea of the Max download size
        // => might not be wize to zip etc on each reset
        //  if ressource consuming and done every minutes of days ?
        $backupTotalSize = $this->humanSize($uSize + $dSize);


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
            $finder = new Finder();
            $finder->files()->in($f);
            $nbFiles = $finder->count();
            // return 'GMT ' . $f->getRelativePathname() . ' [Max ' // TODO : refactor, target data attribute instead of string spliting messing up functionality if syntax change...
            return $f->getRelativePathname() . ' [Max '
                . $this->humanSize(
                    $this->mwsFileSize($f->getPathname())
                ) . " / $nbFiles]";
        }, iterator_to_array($finder, false));
        // dd($bFiles);

        $backupsTotalSize = $this->humanSize(
            $this->mwsFileSize($backupsDir)
        );

        $finder = [];
        if (!empty(glob($uploadTchatSrc))) {
            $finder = new Finder();
            $finder->files()->in($uploadTchatSrc)
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs();
            $finder->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                return strcmp($b->getRealPath(), $a->getRealPath());
            });
        }

        $uploadedTchatFiles = array_map(function (SplFileInfo $f) use ($uploadUriPrefix) {
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
                'uploadsTchatTotalSize' => $uploadsTchatTotalSize,
                'databasesTotalSize' => $databasesTotalSize,
                'gdprBackupSize' => $gdprBackupSize,
                'backupTotalSize' => $backupTotalSize,
                'uploadedTchatFiles' => $uploadedTchatFiles,
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

    // TIPS : 6 month of Miguel Monwoo workload
    //       is around 500Mo with thumb 100
    // public const defaultThumbSize = 100;
    // With half size, it will be ~300Mo
    public const defaultThumbSize = 42;
    #[Route(
        '/backup/download',
        methods: ['POST'],
        name: 'mws_config_backup_download'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
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

        $backupRawName = $request->get('backupRawName', null);
        // $backupName = trim($this->slugger->slug($backupName, '-'));
        $backupName = $this->slugger->slug($backupRawName ?? '', '-');
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

        $srcName = $this->slugger->slug($request->getHost().'.'.$request->getPort());
        $filename = "data" . time() . ".$packageVersion.$srcName$backupName.sqlite";
        $zipName = "backup" . time(). ".$packageVersion.$srcName$backupName";
        $zipFilename = "$zipName.zip";

        $respData = file_get_contents($dbSrc);

        $zipDirSources = [];
        $zipSources = [
            $dbSrc => "$zipName/" . basename($dbSrc),
        ];
        if ($isLightOrFull) {
            $shouldZip = true;
            $uploadSubFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
            $uploadSrc = "$projectDir/$uploadSubFolder";
            $zipDirSources[$uploadSrc] = "$zipName";
        }

        if ($isFull) {
        }
        // dd($shouldZip);
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
                $filesystem = new Filesystem();
                foreach ($zipDirSources as $dirSrc => $dirDst) {
                    if ($filesystem->exists($dirSrc)) {
                        $zipFile->addDirRecursive($dirSrc, $dirDst);
                    }
                }
                foreach ($zipSources as $fileSrc => $fileDst) {
                    if ($filesystem->exists($fileSrc)) {
                        $zipFile->addFile($fileSrc, $fileDst);
                    }
                }

                $zipFile
                    ->outputAsAttachment($zipFilename); // output to the browser without saving to a file
                // TIPS : noting will run after exit of previous call...
                exit(); // BUT sometime, outputAsAttachment will not die...
                // dd('Strange, this code should not run... Missing files in ZIP ?');
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
        '/backup-internal/download',
        // methods: ['POST', 'GET'],
        methods: ['POST'], // TODO : why need to have GET method ? Navigator force get sometime ?
        name: 'mws_config_backup_internal_download'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
    )]
    public function backupInternalDownload(
        Request $request,
    ): Response {
        $user = $this->getUser();
        // dd($mediaPath);
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $csrf = $request->get('_csrf_token', []);
        $csrf = array_shift($csrf);
        // var_dump($csrf); exit();
        if (!$this->isCsrfTokenValid('mws-csrf-config-backup-internal-download', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        // $internalName = $this->slugger->slug(
        //     $request->get('internalName'),
        //     '-',
        // );
        // https://symfony.com/doc/7.1/string.html#slugger
        //             ['en' => ['_' => '_']]
        // $slugger = new AsciiSlugger('en', ['en' => ['_' => '_']]); // NOP, replace is exclusive... not num or letter....
        // $internalName = $slugger->slug($request->get('internalName'), '-', 'en');

        $rawName = $request->get('internalName', []);
        $rawName = array_shift($rawName);
        // var_dump($rawName); exit();
        $internalName = implode('_', array_map(
            [$this->slugger, 'slug'], explode('_', $rawName))
        );
        $internalParts = explode('-', $internalName, 2);
        $rootPackage = \Composer\InstalledVersions::getRootPackage();
        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];

        $srcName = $this->slugger->slug($request->getHost().'.'.$request->getPort());
        // $zipName = "$internalName.$packageVersion.$srcName";

        // $time = (new DateTime())->getTimestamp();
        // TIPS : FOLDER time is GMT TIME, using London as TIME ZONE
        //        will only work in WINTER (they add 1hr in summer)
        //        => use 'UTC' timezone to get UTC right time...
        $formatter = new IntlDateFormatter(
            "fr_FR",
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'UTC', // 'Europe/London', // 'Europe/Paris',
            IntlDateFormatter::TRADITIONAL,
            // https://bugs.php.net/bug.php?id=53939
            // https://www.the-art-of-web.com/php/intl-date-formatter/
            // https://unicode-org.github.io/icu/userguide/format_parse/datetime/
            // "d MMMM yyyy 'à' HH'h'mm", // 'Ymd_His'
            'yyyyMMdd_HHmmss'
        );

        $time = $formatter->parse($internalParts[0]);
        // dump($internalParts[0]);
        // dd((new DateTime())->setTimestamp($time));
        $backupName = ($internalParts[1] ?? null) ? '-' . $internalParts[1] : '';
        $zipName = "backup$time.$packageVersion.$srcName$backupName";
        $zipFilename = "$zipName.zip";

        // dd($internalName);
        if (!$internalName) {
            throw $this->createNotFoundException("Wrong internalName parameter.");
        }
        $projectDir = $this->getParameter('kernel.project_dir');
        $pathRaw = "$projectDir/bckup/$internalName";
        $filesystem = new Filesystem();

        $path = realpath($pathRaw);
        // var_dump($path); exit();
        // dd($path);
        if (!$path || !file_exists($path)) {
            // Please, check apps/mws-sf-pdf-billings/backend/config/packages/mws_moon_manager.yaml:mws_moon_manager.uploadSubFolder etc...
            throw $this->createNotFoundException("Internal backup $internalName not found");
        }
        $projPath = realpath($projectDir);
        if (!starts_with($path, $projPath)) {
            // dd('// TODO TESTs : Do not allow other folders like "../../" ? Browser check it, testable ?');
            // Please, check apps/mws-sf-pdf-billings/backend/config/packages/mws_moon_manager.yaml:mws_moon_manager.uploadSubFolder etc...
            throw $this->createNotFoundException("Internal backup $internalName not found");
        }
        // dd($path);
        // dd($internalName);
        $zipFile = new ZipFile();
        $tmp = "$projectDir/var/cache/tmp.zip";
        try {
            // dd($path);
            $zipFile->addDirRecursive($path, "/$zipName");
            // $zipFile->addDirRecursive($path); // TIPS : let zip filename do init ?
            // $zipFile->close();
            // var_dump($zipFile->count()); exit;
            // var_dump("Fail to build zip backup"); exit();
            // $zipFile->outputAsAttachment("$internalName.zip");
            // TODO : why outputAsAttachment buggy for 2nd bckup export ? save sound better
            // $zipFile->outputAsAttachment("$internalName.zip", null, false);
            // // TIPS : noting will run after exit of previous call...
            // dd('Strange, this code should not run...');

            // Hacky save and download... (TODO : always 1 junk file ?)
            $zipFile->saveAsFile($tmp);
        } catch (ZipException $e) {
            // handle exception
            $this->logger->error(
                "Backup Zip error " . $e->getMessage()
            );
        } finally {
            $zipFile->close();
        }
        // var_dump("Fail to build zip backup"); exit();
        // throw $this->createNotFoundException("Fail to build zip backup.");
        $response = new Response(file_get_contents($tmp));
        $filesystem->remove($tmp);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$zipFilename.'"');

        return $response;
    }


    #[Route(
        '/backup-internal/import',
        // methods: ['POST', 'GET'],
        methods: ['POST'], // TODO : why need to have GET method ? Navigator force get sometime ?
        name: 'mws_config_backup_internal_import'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
    )]
    public function backupInternalImport(
        Request $request,
    ): Response {
        $user = $this->getUser();
        // dd($mediaPath);
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $csrf = $request->get('_csrf_token', []);
        $csrf = array_shift($csrf);
        // var_dump($csrf); exit();
        if (!$this->isCsrfTokenValid('mws-csrf-config-backup-internal-download', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        // TODO : import from zip instead of open folder ? Backup as ZIP instead of simple copy ?
        $rawName = $request->get('internalName', []);
        $rawName = array_shift($rawName);
        // var_dump($rawName); exit();
        $internalName = implode('_', array_map(
            [$this->slugger, 'slug'], explode('_', $rawName))
        );

        // dd($internalName);
        if (!$internalName) {
            throw $this->createNotFoundException("Wrong internalName parameter.");
        }
        $projectDir = $this->getParameter('kernel.project_dir');
        $pathRaw = "$projectDir/bckup/$internalName";
        $filesystem = new Filesystem();

        $path = realpath($pathRaw);
        // var_dump($path); exit();
        // dd($path);
        if (!$path || !file_exists($path)) {
            // Please, check apps/mws-sf-pdf-billings/backend/config/packages/mws_moon_manager.yaml:mws_moon_manager.uploadSubFolder etc...
            throw $this->createNotFoundException("Internal backup $internalName not found");
        }
        $projPath = realpath($projectDir);
        if (!starts_with($path, $projPath)) {
            // dd('// TODO TESTs : Do not allow other folders like "../../" ? Browser check it, testable ?');
            // Please, check apps/mws-sf-pdf-billings/backend/config/packages/mws_moon_manager.yaml:mws_moon_manager.uploadSubFolder etc...
            throw $this->createNotFoundException("Internal backup $internalName not found");
        }

        try {
            // keep possible data updates done before importing data...
            $this->backupInternalSave();
            // clean
            $uploadSubFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
            $uploadSrc = "$projectDir/$uploadSubFolder";

            // $filesystem->remove($uploadSrc); // TIPS : mirror option instead of remove
            // counter TIPS : if no src directory, target directory will keep data, so remove :
            $filesystem->exists($uploadSrc)
            && $filesystem->remove($uploadSrc); 

            // $backupForm->addError(new FormError(
            //     "Backup Zip Did cleanup old uploads."
            // ));

            // load assets
            // $bckupUploadSrc = "$path/messages/tchats";
            $bckupUploadSrc = "$path";
            // dd($bckupUploadSrc);
            // dd($uploadSrc);
            $filesystem->exists($bckupUploadSrc)
            && $filesystem->mirror($bckupUploadSrc, $uploadSrc, null, [
                'override' => true, 'delete' => true,
                'copy_on_windows' => true,
            ]);
            // dd($uploadSrc);

            // dd($path);
            // update db
            // TIPS : load assets before db that will throw if missing db in backup...
            $backupDbSrc = "$path/data.db.sqlite";
            $filesystem->exists($backupDbSrc)
            && $filesystem->copy($backupDbSrc, "$projectDir/var/data.db.sqlite", true);
        } catch (Exception $e) {
            // handle exception
            $this->logger->error(
                "Import internal backup error " . $e->getMessage()
            );
        } finally {

        }

        return $this->redirectToRoute('mws_config_backup');
    }

    #[Route(
        '/backup-internal/remove',
        // methods: ['POST', 'GET'],
        methods: ['POST'], // TODO : why need to have GET method ? Navigator force get sometime ?
        name: 'mws_config_backup_internal_remove'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
    )]
    public function backupInternalRemove(
        Request $request,
    ): Response {
        $user = $this->getUser();
        // dd($mediaPath);
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $csrf = $request->get('_csrf_token', null);
        if (!$this->isCsrfTokenValid('mws-csrf-config-backup-internal-remove', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $rawName = $request->get('internalName', '');
        $internalName = implode('_', array_map(
            [$this->slugger, 'slug'], explode('_', $rawName))
        );
        if (!$internalName || !strlen($internalName)) {
            throw $this->createNotFoundException("Wrong internalName parameter.");
        }
        $projectDir = $this->getParameter('kernel.project_dir');
        $pathRaw = "$projectDir/bckup/$internalName";
        $filesystem = new Filesystem();

        $path = realpath($pathRaw);
        if (!$path || !file_exists($path)) {
            throw $this->createNotFoundException("Internal backup $internalName not found");
        }
        $projPath = realpath($projectDir);
        if (!starts_with($path, $projPath)) {
            throw $this->createNotFoundException("Internal backup $internalName not found");
        }

        try {
            // clean
            $filesystem->exists($path)
            && $filesystem->remove($path); 
        } catch (Exception $e) {
            // handle exception
            $this->logger->error(
                "Remove internal backup error " . $e->getMessage()
            );
        } finally {

        }

        return $this->redirectToRoute('mws_config_backup');
    }

    #[Route(
        '/backup-internal/use-as-gdpr-reset',
        // methods: ['POST', 'GET'],
        methods: ['POST'], // TODO : why need to have GET method ? Navigator force get sometime ?
        name: 'mws_config_backup_internal_use_as_gdpr_reset'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
    )]
    public function backupInternalUseAsGdprReset(
        Request $request,
    ): Response {
        $success = false;
        try {
            $user = $this->getUser();
            if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
                throw $this->createAccessDeniedException('Only for admins');
            }
            // dd($user);
            $csrf = $request->get('_csrf_token', null);
            if (!$this->isCsrfTokenValid('mws-csrf-config-backup-internal-use-as-gdpr-reset', $csrf)) {
                // dd($csrf);
                $this->logger->debug("Fail csrf with", [$csrf, $request]);
                throw $this->createAccessDeniedException('CSRF Expired');
            }
            $rawName = $request->get('internalName', '');
            $internalName = implode('_', array_map(
                [$this->slugger, 'slug'], explode('_', $rawName))
            );
            // dd($internalName);
            if (!$internalName || !strlen($internalName)) {
                throw $this->createNotFoundException("Wrong internalName parameter for $internalName.");
            }
            $projectDir = $this->getParameter('kernel.project_dir');
            // $subFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
            $pathRaw = "$projectDir/bckup/$internalName";
            // $uploadSrc = "$projectDir/$subFolder";
            $filesystem = new Filesystem();

            $path = realpath($pathRaw);
            if (!$path || !file_exists($path)) {
                throw $this->createNotFoundException("Internal backup $internalName not found");
            }
            $projPath = realpath($projectDir);
            if (!starts_with($path, $projPath)) {
                throw $this->createNotFoundException("Internal backup $internalName not found");
            }

            try {
                $backupDbSrc = "$path/data.db.sqlite";
                $backupUploadSrc = "$path";
                $gdprDb = "$projectDir/var/data.gdpr-ok.db.sqlite";
                $gdprUploads = $projectDir . '/var/uploads-gdpr-ok';
                $filesystem->copy($backupDbSrc, $gdprDb, true);
                if (file_exists($backupUploadSrc)) {
                    // if (file_exists($gdprUploads)) {
                    //     $filesystem->remove($gdprUploads);
                    // }
                    $finder = new Finder();
                    // Avoid copy of route backup DB db :
                    // $finder->directories()->in($safeGdprUploads)
                    //     ->ignoreDotFiles(true)
                    //     ->ignoreUnreadableDirs()
                    //     ->depth(0);
                    $finder->in($backupUploadSrc)
                    ->ignoreDotFiles(true)
                    ->ignoreUnreadableDirs()
                    ->notPath('^data\.db\.sqlite');
                    $finder = null;

                    $filesystem->mirror($backupUploadSrc, $gdprUploads, $finder, [
                        'override' => true,
                        'delete' => true,
                    ]);
                    // $filesystem->remove($gdprUploads);
                } else {
                    $this->logger->debug("FAIL : missing $gdprUploads.");
                }
            } catch (Exception $e) {
                $this->logger->error(
                    "Use as GDPR reset internal backup error : " . $e->getMessage()
                );
                throw $this->createNotFoundException("Internal backup error");
            }
            $success = true;
        } catch (Exception $e) {
            // TIPS : already false, assert ? but only warn ? SF Debugger or will need custom stuff ?
            // $success = false;
        }
        return $this->redirectToRoute('mws_config_backup', [
            'useAsGdprResetOk' => $success,
        ]);
    }

    #[Route(
        '/backup-thumbnails/remove',
        methods: ['POST'],
        name: 'mws_config_backup_thumbnails_remove'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_ADMIN . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
    )]
    public function backupThumbnailsRemove(
        Request $request,
    ): Response {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }
        $csrf = $request->get('_csrf_token', []);
        if (!$this->isCsrfTokenValid('mws-csrf-config-backup-thumbnails-remove', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $args = [
            'command' => 'mws:clean-thumbnails',
        ];
        $input = new ArrayInput($args);
        $output = new NullOutput();
        $application->run($input, $output);

        return $this->redirectToRoute('mws_config_backup');
    }

    #[Route(
        '/uploads/{mediaPath<.*$>?}',
        methods: ['GET'],
        name: 'mws_config_upload_proxy'
    )]
    #[SecuAttr(
        "is_granted('" . MwsUser::ROLE_USER . "')",
        statusCode: 401,
        message: MwsLoginFormAuthenticator::t_failToGrantAccess
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
        // $output = new NullOutput();
        
        ob_start();
        $output = new StreamOutput(fopen('php://stdout', 'w'));
        $application->run($input, $output);
        $outputTxt = ob_get_clean(); // TIPS : remove the failing header setup due to TCPDF echo
        $this->logger->debug("bckup Answer :", [ $outputTxt ]);
    }

    #[Route(
        '/json/manifest',
        methods: ['GET'],
        name: 'mws_config_json_manifest'
    )]
    // #[SecuAttr( // TIPS or TODO : IS_ANONYMOUS can't reset parent firewall ?
    // TODO : refactor : send to Public controller ? do not mix public/private ? (hard to fine check secu per route ?)
    //     "is_granted('" . MwsUser::IS_ANONYMOUS . "')",
    //     // MwsUser::IS_GRANTED_ROLE_ADMIN.'',
    //     statusCode: 401,
    //     message: MwsLoginFormAuthenticator::t_failToGrantAccess
    // )]
    public function jsonManifest(
        Request $request,
    ): Response {
        // https://www.jsdiaries.com/how-to-hide-address-bar-in-a-progressive-web-application/
        $manifest = json_decode($this->renderView(
            "@MoonManager/manifest.json",
        ));

        $response = $this->json($manifest);
        // https://symfony.com/doc/6.2/the-fast-track/en/21-cache.html
        $maxAge = 3600 * 5;
        $response->setSharedMaxAge($maxAge);
        // max-age=604800, must-revalidate
        $response->headers->set('Cache-Control', "max-age=$maxAge");
        $response->headers->set('Expires', "$maxAge");
        return $response;
    }

    #[Route(
        '/user/sync',
        name: 'mws_config_user_sync',
        methods: ['POST'],
    )]
    public function qualifSync(
        string|null $viewTemplate,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        /** @var MwsUser $user */
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) {
            $this->logger->debug("Fail auth with", [$request]);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-config-user-sync', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        $configRaw = $request->request->get('config');
        $config = json_decode($configRaw, true);
        $configSync = array_merge(
            $user->getConfig() ?? [],
            $config,
        );
        // dd($configSync);
        $user->setConfig($configSync);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'sync' => [
                'config' => $config,
            ],
            'newCsrf' => $csrfTokenManager->getToken('mws-csrf-config-user-sync')->getValue(),
        ]);
    }
}
