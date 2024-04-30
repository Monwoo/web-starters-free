<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Security;

// https://symfony.com/doc/current/security/access_denied_handler.html

use Psr\Log\LoggerInterface;
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
        protected LoggerInterface $logger,
        protected string $firewallName = "mws_secured_area",
    ) {
        $this->logger->debug("[MwsAuthenticationEntryPoint] DID construct");
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        // add a custom flash message and redirect to the login page
        // $request->getSession()->getFlashBag()->add(
        //     'notice',
        //     $this->translator->trans(
        //         MwsLoginFormAuthenticator::t_accessDenied,
        //         [], 'mws-moon-manager'
        //     )
        // );
        // TIPS :
        // packages/mws-moon-manager/src/EventSubscriber/MwsAccessDeniedSubscriber.php
        // will catch exception and add flashbags...

        // $mwsBackUrl = $this->getTargetPath($request->getSession(), $this->firewallName);
        // $this->removeTargetPath($request->getSession(), $this->firewallName); // TIPS : clean it
        $mwsBackUrl = $request->getSession()->get(
            // Cf :
            // vendor/symfony/security-http/Util/TargetPathTrait.php:36
            '_security.'.$this->firewallName.'.target_path'
        );
        // dd($mwsBackUrl);


        $response = new RedirectResponse($this->urlGenerator->generate(
            MwsLoginFormAuthenticator::LOGIN_ROUTE, [
                // Old way with query string is safer, will also work when
                // reloading from fresh history etc...
                'back-url' => $mwsBackUrl,
            ]
        ));

        $response->headers->set('X-Mws-Back-Url', $mwsBackUrl); // PB : will not be passed by the client side http redirect process
        // $response->headers->set('X-forwarded-for', $mwsBackUrl);
        // dd($mwsBackUrl);
        return $response;
    }
}
