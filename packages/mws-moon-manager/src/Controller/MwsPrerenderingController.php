<?php

namespace MWS\MoonManagerBundle\Controller;

use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// https://symfony.com/bundles/SensioFrameworkExtraBundle/current/annotations/security.html
//     "is_granted('ROLE_USER') and is_granted('ROLE_MWS_USER')",
// TODO : translated annotation ? otherwise use messages IDs...
#[Route('/{_locale<%app.supported_locales%>}/prerendering')]
#[Security(
    "is_granted('ROLE_USER') and is_granted('ROLE_ADMIN')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsPrerenderingController extends AbstractController
{
    #[Route('/', name: 'mws_prerendering')]
    public function index(): Response
    {
        // TODO : list all pre-rendered elements (+ allow detail view on click ?)
        return $this->render('@MoonManager/prerendering/index.html.twig', [

        ]);
    }
}
