<?php

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use MWS\MoonManagerBundle\Form\MwsUserFilterType;
use MWS\MoonManagerBundle\Repository\MwsOfferRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale<%app.supported_locales%>}/mws-offer',
    options: ['expose' => true],
)]
class MwsOfferController extends AbstractController
{
    public function __construct(
        protected LoggerInterface $logger,
        protected SerializerInterface $serializer,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $em
    ){
    }
    
    #[Route('/', name: 'mws_offer')]
    public function index(): Response
    {
        // TODO : depending of user roles : will have different preview systems
        return $this->redirectToRoute(
            'mws_offer_list',
            array_merge($request->query->all(), [
                // "filterTags" => $filterTags,
                // "keyword" => $keyword
            ]),
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/lookup/{viewTemplate?}', name: 'mws_offer_lookup')]
    public function lookup(
        $viewTemplate,
        MwsOfferRepository $mwsOfferRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('mws_user_login');
        }

        $qb = $mwsOfferRepository->createQueryBuilder('u');
        $query = $qb->getQuery();
        // dd($query->getResult());    
        $offers = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        $this->logger->debug("Succeed to list users");

        $lastSearch = [
            // TIPS urlencode() will use '+' to replace ' '
            "jsonResult" => rawurlencode(json_encode([
                // "searchKeyword" => $keyword,
                "searchKeyword" => null,
            ])),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/mws-user/survey-js-models/MwsUserFilterType.json.twig"
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $filterForm = $this->createForm(MwsUserFilterType::class, $lastSearch);
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
                $keyword = $surveyAnswers['searchKeyword'] ?? null;
                return $this->redirectToRoute(
                    'mws_user_list',
                    array_merge($request->query->all(), [
                        "filterTags" => $filterTags,
                        "keyword" => $keyword
                    ]),
                    Response::HTTP_SEE_OTHER
                );
            }
        }



        return $this->render('@MoonManager/mws_offer/lookup.html.twig', [
            'offers' => $offers,
            'lookupForm' => $filterForm,
            'viewTemplate' => $viewTemplate,
        ]);
    }
}
