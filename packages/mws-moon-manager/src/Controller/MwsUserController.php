<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale<%app.supported_locales%>}/mws-user')]
class MwsUserController extends AbstractController
{

    public function __construct(
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $em
    ){
    }

    #[Route('/',
        name: 'mws_user',
        options: ['expose' => true],
    )]
    public function index(): Response
    {
        return $this->render('@MoonManager/mws-user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/login',
        name: 'mws_user_login',
        options: ['expose' => true],
    )]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        // $this->translator->trans('MwsLoginFormAuthenticator.accessDenied', [], 'mws-moon-manager');

        // new FlashBag();
        $flashBag = $request->getSession()->getFlashBag();
        if ($this->getUser() && !count($flashBag->keys())) {
            return $this->redirectToRoute(MwsLoginFormAuthenticator::SUCCESS_LOGIN_ROUTE);
        }

        // get the login error if there is one
        // TODO : no error on csrf token error ?
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('@MoonManager/mws-user/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/logout',
        name: 'mws_user_logout',
        options: ['expose' => true],
    )]
    public function logout(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

}
