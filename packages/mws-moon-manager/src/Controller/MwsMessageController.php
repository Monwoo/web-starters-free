<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Entity\MwsMessage;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsMessageImportType;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use MWS\MoonManagerBundle\Repository\MwsMessageRepository;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route(
    '/{_locale<%app.supported_locales%>}/mws-message',
    options: ['expose' => true],
)]
#[SecuAttr(
    "is_granted('ROLE_USER')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsMessageController extends AbstractController
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

    #[Route('/list/{viewTemplate<[^/]*>?}', name: 'mws_message_list')]
    public function list(
        $viewTemplate,
        Request $request,
        MwsMessageRepository $mwsMessageRepository,
        PaginatorInterface $paginator,
    ): Response
    {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::$ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        // $messages = $mwsMessageRepository->findBy([
        //     'owner' => $user,
        // ]);
        $qb = $mwsMessageRepository->createQueryBuilder('m');

        $query = $qb->getQuery();
        // dd($query->getResult());    
        $messages = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $request->query->getInt('pageLimit', 10), /*page number*/
        );
        $addMessageConfig = [
            "jsonResult" => rawurlencode(json_encode([
                // "searchKeyword" => $keyword,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsMessageType.json.twig",
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $addMessageForm = $this->createForm(MwsSurveyJsType::class, $addMessageConfig);
        $addMessageForm->handleRequest($request);

        if ($addMessageForm->isSubmitted()) {
            $this->logger->debug("Did submit addMessageForm");

            if ($addMessageForm->isValid()) {
                $this->logger->debug("addMessageForm ok");
                // dd($addMessageForm);

                $surveyAnswers = json_decode(
                    urldecode($addMessageForm->get('jsonResult')->getData()),
                    true
                );
                // dd($surveyAnswers);
                $projectId = $surveyAnswers['projectId'] ?? null;
                $destId = $surveyAnswers['destId'] ?? null;
                $sourceId = $surveyAnswers['sourceId'] ?? null;
                // $monwooAmount = $surveyAnswers['monwooAmount'] ?? null;
                // $projectDelayInOpenDays = $surveyAnswers['projectDelayInOpenDays'] ?? null;
                // $asNewOffer = $surveyAnswers['asNewOffer'] ?? null;

                $msg = $mwsMessageRepository->findOneBy([
                    'projectId' => $projectId,
                    'destId' => $destId,
                    'sourceId' => $sourceId,
                    'owner' => $user,
                ]);
                // $msg = $mwsMessageRepository->createQueryBuilder('m');
                if (!$msg) {
                    $msg = new MwsMessage();
                    $msg->setOwner($user);
                }
                // dd($msg);
                $sync = function ($path, $ko = null) use ($surveyAnswers, $msg) {
                    $set = 'set' . ucfirst($path);
                    // $get = 'get' . ucfirst($path);
                    // $v =  $inputMessage->$get();
                    $v =  $surveyAnswers[$path] ?? $ko;
                    if (
                        null !== $v &&
                        ((!is_string($v)) || strlen($v))
                    ) {
                        $msg->$set($v);
                    }
                };

                // dd($surveyAnswers);
                $sync('projectId');
                $sync('destId'); // TODO : validation error : can't be empty
                $sync('monwooAmount');
                $sync('projectDelayInOpenDays');
                $sync('asNewOffer');
                $sync('isDraft', true);
                // $msg->setAsNewOffer("Oui" === ($surveyAnswers['asNewOffer'] ?? null));
                $sync('sourceId');
                $sync('messages');
                $sync('crmLogs');

                // doing cleanup
                $cleanMsgs = [];
                foreach($msg->getMessages() as $msgTchat) {
                    $uploadFiles = $msgTchat['uploadFile'] ?? null; // TODO : refactor for multiples files ?
                    if ($uploadFiles && count($uploadFiles)) {
                        // $uploadFile = $uploadFiles[0];
                        if ($msgTchat['deleteUpload']) {
                            // TODO : clean not used upload path ? or keep for restore and clean after long non usage ? tag as trash ?
                            unset($msgTchat['uploadFile']);
                            $msgTchat['deleteUpload'] = false;
                        }
                    }
                    $cleanMsgs[] = $msgTchat;
                };
                $msg->setMessages($cleanMsgs);

                // $sync('messages');
                // Save the submited message :
                $this->em->persist($msg);
                $this->em->flush();
            }
        }

        // TODO : import some data, then display :
        return $this->render('@MoonManager/mws_message/list.html.twig', [
            'viewTemplate' => $viewTemplate,
            'messages' => $messages,
            'addMessageForm' => $addMessageForm,
        ]);
    }

    #[Route(
        '/import/{viewTemplate<[^/]*>?}/{format}',
        name: 'mws_message_import',
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
        MwsMessageRepository $mwsMessageRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::$ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $maxTime = 60 * 30; // 30 minutes max
        set_time_limit($maxTime);
        ini_set('max_execution_time', $maxTime);

        $forceRewrite = $request->query->get('forceRewrite', false);
        $forceStatusRewrite = $request->query->get('forceStatusRewrite', false);
        $forceCleanTags = $request->query->get('forceCleanTags', false);

        $uploadData = null;
        $form = $this->createForm(MwsMessageImportType::class, $uploadData);
        $form->handleRequest($request);
        $reportSummary = "";

        $this->logger->debug("Succeed to handle Request");
        if ($form->isSubmitted()) {
            $this->logger->debug("Succeed to submit form");

            if ($form->isValid()) {
                $this->logger->debug("Form is valid : ok");
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
                    $safeFilename = $this->slugger->slug($originalName);

                    $newFilename = $safeFilename . '_' . uniqid() . '.' . $extension;

                    $importContent = file_get_contents($importedUpload->getPathname());
                    $importContentEncoding = mb_detect_encoding($importContent);
                    // dd($importContentEncoding);
                    $importContent = iconv($importContentEncoding, "UTF-8", $importContent);
                    // TIPS : clean as soon as we can...
                    unlink($importedUpload->getPathname());
                    // $reportSummary = $importContent;
                    /** @var MwsOffer[] */
                    $messagesDeserialized = $this->deserializeMessages(
                        $user,
                        $importContent,
                        $format,
                        $newFilename,
                    );
                    // dd($messagesDeserialized);

                    $savedCount = 0;
                    /** @var MwsMessage $message */
                    foreach ($messagesDeserialized as $idx => $message) {
                        $destId = $message->getDestId();
                        $projectId = $message->getProjectId();
                        $sourceId = $message->getSourceId();
                        $owner = $message->getOwner();
                        // $email = $message->getContact1();
                        // $phone = $message->getContact2();

                        // TODO : add as repository method ?
                        $qb = $mwsMessageRepository
                            ->createQueryBuilder('m')
                            ->where('m.destId = :destId')
                            ->andWhere('m.projectId = :projectId')
                            ->andWhere('m.sourceId = :sourceId')
                            ->andWhere('m.owner = :owner')
                            ->setParameter('destId', $destId)
                            ->setParameter('projectId', $projectId)
                            ->setParameter('sourceId', $sourceId)
                            ->setParameter('owner', $owner);
                        $query = $qb->getQuery();

                        $allDuplicates = $query->execute();

                        // dd($allDuplicates);
                        if ($allDuplicates && count($allDuplicates)) {
                            if ($forceRewrite) {
                                $reportSummary .= "<strong>Surcharge le doublon : </strong> [$destId , $projectId , $sourceId , $owner]<br/>";
                                $inputMessage = $message;
                                // $message = $allDuplicates[0];
                                $message = array_shift($allDuplicates);
                                $sync = function ($path) use ($inputMessage, $message) {
                                    $set = 'set' . ucfirst($path);
                                    $get = 'get' . ucfirst($path);
                                    $v =  $inputMessage->$get();
                                    if (
                                        null !== $v &&
                                        ((!is_string($v)) || strlen($v))
                                    ) {
                                        $message->$set($v);
                                    }
                                };
                                // TODO : factorize code with serializer service ? factorize to same location...
                                $sync('clientUsername');
                                $sync('destId');
                                $sync('monwooAmount');
                                $sync('projectDelayInOpenDays');
                                $sync('asNewOffer');
                                $sync('isDraft');
                                $sync('sourceId');
                                $sync('crmLogs');
                                $sync('messages');
                                $sync('owner');
                                // dd($message);
                                // CLEAN all possible other duplicates :
                                foreach ($allDuplicates as $otherDups) {
                                    $this->em->remove($otherDups);
                                }
                                // $savedCount++;
                            } else {
                                $reportSummary .= "<strong>Ignore le doublon : </strong> [$destId , $projectId , $sourceId , $owner]<br/>";
                                continue; // TODO : WHY BELOW counting one write when all is duplicated ?
                            }
                        }

                        // TODO : add comment to some traking entities, column 'Observations...' or too huge for nothing ?
                        $this->em->persist($message);
                        $this->em->flush();
                        $savedCount++;
                    }
                    $reportSummary .= "<br/><br/>Enregistrement de $savedCount messages OK <br/>";

                    // var_dump($extension);var_dump($importContent);var_dump($messagesDeserialized); exit;
                }
            }
        }
        $formatToText = [
            // 'tsv' => "Tab-separated values (TSV)",
            'csv' => "Comma-separated values (CSV)",
            'json' => "JavaScript Object Notation (JSON)",
        ];
        return $this->render('@MoonManager/mws_message/import.html.twig', [
            'reportSummary' => $reportSummary,
            'format' => $format,
            'uploadForm' => $form,
            'viewTemplate' => $viewTemplate,
            'title' => 'Importer les messages via ' . ($formatToText[$format] ?? $format)
        ]);
    }
    
    #[Route(
        '/delete-all/{viewTemplate<[^/]*>?}',
        name: 'mws_message_delete_all',
        methods: ['POST'],
    )]
    public function deleteAll(
        string|null $viewTemplate,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $user = $this->getUser();
        // TIPS : firewall, middleware or security guard can also
        //        do the job. Double secu prefered ? :
        if (!$user) { // TODO : only for admin too ?
            $this->logger->debug("Fail auth with", [$request]);
            throw $this->createAccessDeniedException('Only for logged users');
        }
        $csrf = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('mws-csrf-message-delete', $csrf)) {
            $this->logger->debug("Fail csrf with", [$csrf, $request]);
            throw $this->createAccessDeniedException('CSRF Expired');
        }
        
        // dd($tag);
        // $tag->removeMwsOffer($offer);

        $qb = $this->em->createQueryBuilder()
        ->delete(MwsMessage::class, 'u');               

        $query = $qb->getQuery();
        // dump($query->getSql());

        $resp = $query->execute();
        $this->em->flush();    

        // return $this->json([
        //     'delete' => 'ok',
        //     'newCsrf' => $csrfTokenManager->getToken('mws-csrf-message-delete')->getValue(),
        // ]);
        return $this->redirectToRoute(
            'mws_message_list',
            [ // array_merge($request->query->all(), [
                "viewTemplate" => $viewTemplate,
                "page" => 1,
            ], //),
            Response::HTTP_SEE_OTHER
        );
    }

    // TODO : more like 'loadMessages' than deserialize,
    //        will save in db too for code factorisation purpose...
    public function deserializeMessages($user, $data, $format, $sourceFile)
    {
        /** @param MwsMessage[] **/
        $out = null;
        // TODO : add custom serializer format instead of if switch ?
        if ($format === 'monwoo-extractor-export') {
            $data = json_decode($data, true);
            $out = [];

            foreach ($data as $sourceSlug => $board) {
                // $contactIndex = $board['users'] ?? [];
                foreach (($board ?? []) as $projectId => $m) {
                    $message = new MwsMessage();

                    $cleanUp = function ($val) {
                        return $val ? trim(
                            // NO-BREAK SPACE (U+A0)
                            preg_replace(
                                '/(\xc2\xa0)+/',
                                ' ',
                                preg_replace('/[^\S\r\n]+/', ' ', $val)
                            )
                        ) : null;
                    };
                    $message->setProjectId($cleanUp($m["projectId"] ?? null));
                    $message->setDestId($cleanUp($m["destId"]));
                    $message->setMonwooAmount($m["monwooAmount"] ?? null);
                    $message->setProjectDelayInOpenDays($m["projectDelayInOpenDays"] ?? null);
                    $message->setAsNewOffer($m["asNewOffer"] ?? null);
                    $message->setIsDraft($m["isDraft"] ?? true);
                    $message->setSourceId($m["sourceId"] ?? $sourceFile); // TODO : track ?
                    $message->setCrmLogs($m["crmLogs"] ?? null);
                    $message->setMessages($m["messages"] ?? null);
                    $message->setOwner($user);

                    $out[] = $message;
                }
            }
        } else {
            $out = $this->serializer->deserialize(
                $data,
                MwsMessage::class . "[]",
                $format,
                // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            );
        }
        return $out;
    }

    /** @param MwsMessage[] $offers */
    public function serializeMessages($offers, $format)
    {
        $out = null;
        // TODO : add custom serializer format instead of if switch ?
        if ($format === 'monwoo-extractor-export') {
        } else {
            $out = $this->serializer->serialize(
                $offers,
                MwsMessage::class . "[]",
                $format,
                // TIPS : [CsvEncoder::DELIMITER_KEY => ';'] for csv format...
            );
        }
        return $out;
    }
}
