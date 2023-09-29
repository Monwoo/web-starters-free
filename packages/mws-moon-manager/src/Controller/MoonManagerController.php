<?php

namespace MWS\MoonManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoonManagerController extends AbstractController
{
    #[Route('/moon-manager', name: 'app_moon_manager')]
    public function index(): Response
    {
        return $this->render('@MoonManager/index.html.twig', [
            'controller_name' => 'MoonManagerController',
        ]);
    }
}
