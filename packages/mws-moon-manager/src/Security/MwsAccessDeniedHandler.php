<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Security;

// https://symfony.com/doc/current/security/access_denied_handler.html

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MwsAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // add a custom flash message and redirect to the login page
        $request->getSession()->getFlashBag()->add(
            'notice',
            $this->translator->trans(MwsLoginFormAuthenticator::t_accessDenied)
            // $this->translator->trans('MwsLoginFormAuthenticator.accessDenied', [], 'mws-moon-manager')
        );

        return new RedirectResponse($this->urlGenerator->generate(
            MwsLoginFormAuthenticator::LOGIN_ROUTE
        ));

        // TODO : will need to warn if missing role issue ?
        // return new Response($content, 403);
    }
}
