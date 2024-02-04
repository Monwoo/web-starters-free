<?php

namespace MWS\MoonManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MwsTimingsController extends AbstractController
{
    #[Route('/mws/timings', name: 'app_mws_timings')]
    public function index(): Response
    {
        return $this->render('mws_timings/index.html.twig', [
            'controller_name' => 'MwsTimingsController',
        ]);
    }
}
