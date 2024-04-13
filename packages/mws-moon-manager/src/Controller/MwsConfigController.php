<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;

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
    ): Response {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        // $csrf = $request->request->get('_csrf_token');
        // if (!$this->isCsrfTokenValid('mws-csrf-config-backup', $csrf)) {
        //     $this->logger->debug("Fail csrf with", [$csrf, $request]);
        //     throw $this->createAccessDeniedException('CSRF Expired');
        // } // Classic SF form already have CSRF Validations....

        $lastSearch = [
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
        $backupForm = $this->createForm(MwsSurveyJsType::class, $lastSearch);
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
            return strcmp($a->getRealPath(), $b->getRealPath());
        });
        // $nbFiles = iterator_count($finder);
        // $maxFileIndex = iterator_count($finder) - 1;
        // dd(iterator_to_array($finder, false));
        $bFiles = array_map(function(SplFileInfo $f) {
            return $f->getRelativePathname();
        }, iterator_to_array($finder, false));
        // dd($bFiles);

        return $this->render('@MoonManager/mws_config/backup.html.twig', [
            'backupForm' => $backupForm,
            'backups' => $bFiles,
        ]);
    }

    public const defaultThumbSize = 100;
    #[Route('/backup/download',
    methods: ['POST'],
    name: 'mws_config_backup_download')]
    public function fetchRootUrl(
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Only for logged users');
        }

        $url = $request->query->get('url', null);
        $keepOriginalSize = $request->query->get('keepOriginalSize', null);
        $thumbnailSize = intval($request->query->get('thumbnailSize', 0));
        if (!$thumbnailSize) {
            // TODO : default from session or db config params ?
            $thumbnailSize = self::defaultThumbSize;
        }
        // dd($thumbnailSize);
        $this->logger->debug("Will fetch url : $url");

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
                throw $this->createNotFoundException('Media path not allowed');
            }
            $url = $path;
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
            }
        } catch (\Exception $e) {
            $this->logger->warning($e);
            // dd($e);
            throw $this->createNotFoundException('Fail for url ' . $url);
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
}
