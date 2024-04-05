<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\EventSubscriber;

// https://symfony.com/doc/current/security/access_denied_handler.html

use MWS\MoonManagerBundle\Security\MwsLoginFormAuthenticator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class MwsAccessDeniedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RequestStack $requestStack,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // the priority must be greater than the Security HTTP
            // ExceptionListener, to make sure it's called before
            // the default exception listener
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        // ... perform some action (e.g. logging)

        // optionally set the custom response
        // $event->setResponse(new Response(null, 403));

        // or stop propagation (prevents the next exception listeners from being called)
        //$event->stopPropagation();

        // add a custom flash message and redirect to the login page
        $request = $this->requestStack->getCurrentRequest();
        $request->getSession()->getFlashBag()->add(
            'notice',
            $this->translator->trans(MwsLoginFormAuthenticator::t_accessDenied)
        ); // TODO : useless listener, easyest to do inside : ?
        // packages/mws-moon-manager/src/Security/MwsAccessDeniedHandler.php:27 ?

        // TIPS : below will BREAK the 'Default Target Path Behavior' ? keep it as exception
        // https://symfony2-document.readthedocs.io/en/latest/cookbook/security/target_path.html
        // https://symfonycasts.com/screencast/fosuserbundle/guard-authenticator#play
        // https://symfonycasts.com/screencast/symfony4-security/target-path
        // https://symfony.com/doc/current/security.html#customize-successful-and-failed-authentication-behavior
        // $event->setResponse(new RedirectResponse($this->urlGenerator->generate(
        //     MwsLoginFormAuthenticator::LOGIN_ROUTE
        // )));
    }
}
