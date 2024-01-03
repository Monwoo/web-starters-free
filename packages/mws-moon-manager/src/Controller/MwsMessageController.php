<?php

namespace MWS\MoonManagerBundle\Controller;

use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale<%app.supported_locales%>}/mws-message',
    options: ['expose' => true],
)]
#[Security(
    "is_granted('ROLE_USER')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsMessageController extends AbstractController
{
    #[Route('/', name: 'mws_message_list')]
    public function index(): Response
    {
        return $this->render('@MoonManager/mws_message/list.html.twig', [
            'controller_name' => 'MwsMessageController',
        ]);
    }
}
