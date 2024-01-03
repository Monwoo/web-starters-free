<?php

namespace MWS\MoonManagerBundle\Controller;

use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale<%app.supported_locales%>}/mws-calendar',
    options: ['expose' => true],
)]
#[Security(
    "is_granted('ROLE_USER')",
    statusCode: 401,
    message: MwsLoginFormAuthenticator::t_failToGrantAccess
)]
class MwsCalendarController extends AbstractController
{
    #[Route('/index', name: 'mws_calendar_index')]
    public function index(): Response
    {
        return $this->render('@MoonManager/mws_calendar/index.html.twig', [
        ]);
    }

    #[Route('/add-event', name: 'mws_calendar_add_event')]
    public function add_event(): Response
    {
        return $this->render('@MoonManager/mws_calendar/add-event.html.twig', [
        ]);
    }
}
