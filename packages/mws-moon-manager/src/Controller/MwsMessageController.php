<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use MWS\MoonManagerBundle\Entity\MwsMessage;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Form\MwsMessageImportType;
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
        MwsMessageRepository $mwsMessageRepository,
    ): Response
    {
        $user = $this->getUser();
        if (!$user || !$this->security->isGranted(MwsUser::$ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only for admins');
        }

        $messages = $mwsMessageRepository->findBy([
            'owner' => $user,
        ]);
        // TODO : import some data, then display :
        return $this->render('@MoonManager/mws_message/list.html.twig', [
            'viewTemplate' => $viewTemplate,
            'messages' => $messages
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
                                $sync('sourceId');
                                $sync('crmLogs');
                                $sync('messages');
                                $sync('owner');
                                // dd($message);
                                // CLEAN all possible other duplicates :
                                foreach ($allDuplicates as $otherDups) {
                                    $this->em->remove($otherDups);
                                }
                                                        // TODO : add comment to some traking entities, column 'Observations...' or too huge for nothing ?
                                $this->em->persist($message);
                                $this->em->flush();
                                $savedCount++;
                            } else {
                                $reportSummary .= "<strong>Ignore le doublon : </strong> [$destId , $projectId , $sourceId , $owner]<br/>";
                                continue;
                            }
                        }
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
                foreach (($board ?? []) as $offerSlug => $o) {
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
                    $message->setProjectId($cleanUp($o["projectId"] ?? null));
                    $message->setDestId($cleanUp($o["destId"]));
                    $message->setMonwooAmount($o["monwooAmount"] ?? null);
                    $message->setProjectDelayInOpenDays($o["projectDelayInOpenDays"] ?? null);
                    $message->setDestId($cleanUp($o["destId"] ?? null));
                    $message->setSourceId($o["sourceId"] ?? $sourceFile); // TODO : track ?
                    $message->setCrmLogs($o["crmLogs"] ?? null);
                    $message->setMessages($o["messages"] ?? null);
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
