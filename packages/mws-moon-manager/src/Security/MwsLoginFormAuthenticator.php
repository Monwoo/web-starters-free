<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class MwsLoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'mws_user_login';
    public const SUCCESS_LOGIN_ROUTE = 'mws_moon_manager';

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        protected LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator,
    )
    {
        $this->logger->debug("[MwsLoginFormAuthenticator] DID construct");
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): bool
    {
        // https://github.com/symfony/symfony/discussions/42521
        // return $request->isMethod('POST') && $this->getLoginUrl($request) === $request->getPathInfo();
        // To allow login check on subfolders,
        // $this->getLoginUrl($request) if full url whereas $request->getPathInfo() is subpath url...
        return $request->isMethod('POST') && $this->getLoginUrl($request) === $request->getRequestUri();
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        $this->logger->debug("[MwsLoginFormAuthenticator] will try authenticate for $username");
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->debug("[MwsLoginFormAuthenticator] onAuthenticationSuccess");
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::SUCCESS_LOGIN_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        $this->logger->debug("[MwsLoginFormAuthenticator] getLoginUrl for {$request->getRequestUri()} at {$request->getBaseUrl()}");
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
