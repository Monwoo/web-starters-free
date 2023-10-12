<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Security;

// https://symfony.com/doc/current/security/access_denied_handler.html
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MwsAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        // add a custom flash message and redirect to the login page
        $request->getSession()->getFlashBag()->add(
            'notice',
            $this->translator->trans(
                MwsLoginFormAuthenticator::t_accessDenied,
                [], 'mws-moon-manager'
            )
        );

        return new RedirectResponse($this->urlGenerator->generate(
            MwsLoginFormAuthenticator::LOGIN_ROUTE
        ));
    }
}
