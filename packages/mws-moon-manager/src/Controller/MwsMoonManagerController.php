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
#[Route('/{_locale<%app.supported_locales%>}/moon-manager')]
#[Security(
    "is_granted('ROLE_USER')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsMoonManagerController extends AbstractController
{
    #[Route('/', name: 'mws_moon_manager')]
    public function index(): Response
    {
        return $this->render('@MoonManager/mws-moon-manager/index.html.twig', [
            'controller_name' => 'MoonManagerController',
        ]);
    }
}
