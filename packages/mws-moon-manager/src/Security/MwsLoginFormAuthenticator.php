<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
// use Symfony\Component\Security\Core\Security; // Depreciated
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

// https://symfony.com/doc/current/security/custom_authenticator.html
class MwsLoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'mws_user_login';
    public const SUCCESS_LOGIN_ROUTE = 'mws_moon_manager';
    public const t_failToGrantAccess = 'MwsLoginFormAuthenticator.failToGrandAccess';
    public const t_accessDenied = 'MwsLoginFormAuthenticator.accessDenied';

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
        // dump($request->isMethod('POST'));
        // dump($this->getLoginUrl($request));
        // dd($request->getRequestUri());
        return $request->isMethod('POST') && $this->getLoginUrl($request) === $request->getRequestUri();
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username', '');
        $this->logger->debug("[MwsLoginFormAuthenticator] will try authenticate for $username");
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            // new UserBadge($username, function ($userIdentifier) {
            //     return $this->mwsUserRepository->findOneBy(['email' => $userIdentifier]);
            // }),        
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->debug("[MwsLoginFormAuthenticator] onAuthenticationSuccess");
        // // on success, let the request continue
        // return null;

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::SUCCESS_LOGIN_ROUTE));
    }

    // public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    // {
    //     $data = [
    //         // you may want to customize or obfuscate the message first
    //         'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

    //         // or to translate this message
    //         // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
    //     ];

    //     return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    // }
    protected function getLoginUrl(Request $request): string
    {
        $this->logger->debug("[MwsLoginFormAuthenticator] getLoginUrl for {$request->getRequestUri()} at {$request->getBaseUrl()}");
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
