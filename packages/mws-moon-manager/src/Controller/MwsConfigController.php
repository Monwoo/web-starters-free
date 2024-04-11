<?php

namespace MWS\MoonManagerBundle\Controller;

use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecuAttr;

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
    #[Route('/backup', name: 'mws_config_backup')]
    public function index(): Response
    {
        return $this->render('@MoonManager/mws_config/backup.html.twig', [
            'controller_name' => 'MwsConfigController',
        ]);
    }
}
