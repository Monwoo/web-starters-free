<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

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
    // TIPS :
    // Route will be available JS side thanks to fos:js-routing
    // and options: ['expose' => true],
    // Check with :
    // php bin/console fos:js-routing:debug
    #[Route('/',
        name: 'mws_moon_manager',
        options: ['expose' => true],
    )]
    public function index(): Response
    {
        return $this->render('@MoonManager/mws_moon_manager/index.html.twig', [
            'controller_name' => 'MoonManagerController',
        ]);
    }
}
