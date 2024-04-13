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
}
