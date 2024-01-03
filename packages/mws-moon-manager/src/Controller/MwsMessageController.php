<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ){
    }

    #[Route('/', name: 'mws_message_list')]
    public function index(): Response
    {
        $messages = [];
        // TODO : import some data, then display :
        return $this->render('@MoonManager/mws_message/list.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route(
        '/import/{viewTemplate}/{format}',
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
        MwsOfferRepository $mwsOfferRepository,
        MwsOfferStatusRepository $mwsOfferStatusRepository,
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
                        $mwsOfferStatusRepository,
                        $mwsOfferRepository
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
                                if ($forceStatusRewrite) {
                                    $sync('currentStatusSlug');
                                }
                                if ($forceCleanTags) {
                                    $offer->getTags()->clear();
                                }

                                $tags = $inputOffer->getTags();
                                foreach ($tags as $tag) {
                                    // TIPS : inside addTag, only one by specific only one choice category type ?
                                    // OK to copy all, clone src use
                                    // $mwsOfferRepository->addTag($offer, $tag);
                                    $offer->addTag($tag);
                                }

                                $offer->getContacts()->clear();
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
                            } else {
                                $reportSummary .= "<strong>Ignore le doublon : </strong> [$sourceName,  $slug]<br/>";
                                continue;
                            }
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
}
