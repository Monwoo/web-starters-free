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
use Symfony\Contracts\Translation\TranslatorInterface;

// TODO : how to use 'trans' with function notation inside const ?
function trans(...$args)
{
    // TIPS : only for text string extractor to work,
    // dummy function for i18n simple extractions
    return $args[0] ?? null;
}
// define('trans', function (...$args) {
//     // TIPS : only for text string extractor to work,
//     // dummy function for i18n simple extractions
//     return $args[0] ?? null;
// });

// But below will need php pre-processings, so avoid class constant for translations msgs :
// https://www.sitepoint.com/php-macros-for-fun-and-profit/
// macro {
//     unless (···condition) { ···body }
// } >> {
//     if (!(···condition)) { ···body }
// }

// BELOW const, juste for translator extractor to detect them :
define(
    't_MwsLoginFormAuthenticator_failToGrantAccess',
    trans('MwsLoginFormAuthenticator.failToGrantAccess', [], 'mws-moon-manager')
);
define(
    't_MwsLoginFormAuthenticator_accessDenied',
    trans('MwsLoginFormAuthenticator.accessDenied', [], 'mws-moon-manager')
);

// https://symfony.com/doc/current/security/custom_authenticator.html
class MwsLoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'mws_user_login';
    // public const SUCCESS_LOGIN_ROUTE = 'mws_moon_manager';
    public const SUCCESS_LOGIN_ROUTE = 'app_home';
    public const t_failToGrantAccess = t_MwsLoginFormAuthenticator_failToGrantAccess;
    public const t_accessDenied = t_MwsLoginFormAuthenticator_accessDenied;

    public function __construct(
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected UrlGeneratorInterface $urlGenerator,
        // TODO : service or doc and/or recipe ?
        protected string $firewallName = "mws_secured_area",
    ) {
        $this->logger->debug("[MwsLoginFormAuthenticator] DID construct");
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
        $willSupport = $request->isMethod('POST')
            && $this->getLoginUrl($request) === $request->getRequestUri();
        $this->logger->debug("[MwsLoginFormAuthenticator] Will Support : $willSupport");
        if (!$willSupport) {
            $lastTargetPath = $this->getTargetPath($request->getSession(), $this->firewallName);
            if (!$lastTargetPath) {
                // $lastTargetPath = $request->getUri();
                // $this->saveTargetPath($request->getSession(), $this->firewallName, $lastTargetPath);
                // $this->logger->debug("[MwsLoginFormAuthenticator] Did setup lastTargetPath to : $lastTargetPath");
            } else {
                $this->logger->debug("[MwsLoginFormAuthenticator] Did reuse lastTargetPath as : $lastTargetPath");
            }
        }
        $mwsBackUrl = $request->headers->get('X-Mws-Back-Url') ?? $request->get('back-url') ?? null;
        // dd($request->query);
        // dd($mwsBackUrl);
        if ($mwsBackUrl) {
            // dd($mwsBackUrl);
            $this->saveTargetPath($request->getSession(), $this->firewallName, $mwsBackUrl);
        }

        return $willSupport;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username', '');
        $this->logger->debug("[MwsLoginFormAuthenticator] will try authenticate for $username");
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        // TODO : exceptions listeners are breaking regular exception last path stuff ?
        // https://github.com/symfony/security-http/blob/6.3/Firewall/ExceptionListener.php#L195
        // session isn't required when using HTTP basic authentication mechanism for example
        // TODO : missing redirect to non-granted pages after successful login...
        // if ($request->hasSession() && $request->isMethodSafe() && !$request->isXmlHttpRequest()) {
        //     $this->saveTargetPath($request->getSession(), $this->firewallName, $request->getUri());
        // }

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

        $this->firewallName = $firewallName;
        // TIPS : better send back url in param ?
        //        session for multiple window open will redirect only for 
        //        first logged window and wipe out redirect for others 
        //        if did refresh all before login and only use session system...

        // X- header is not enough, header might not get transfered...
        // https://github.com/whatwg/fetch/issues/553
        $mwsBackUrl = $request->headers->get('X-Mws-Back-Url') ?? $request->get('back-url') ?? null;
        // dd($request->query);
        // dd($mwsBackUrl);
        if ($mwsBackUrl) {
            // dd($mwsBackUrl);
            $this->logger->debug("[MwsLoginFormAuthenticator] mwsBackUrl", [$mwsBackUrl]);
            $this->removeTargetPath($request->getSession(), $firewallName); // TIPS : clean it
            return new RedirectResponse($mwsBackUrl);
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            $this->logger->debug("[MwsLoginFormAuthenticator] targetPathRedirect", [$targetPath]);
            $this->removeTargetPath($request->getSession(), $firewallName); // TIPS : clean it
            return new RedirectResponse($targetPath);
        }

        $this->logger->debug("[MwsLoginFormAuthenticator] classicLogin", [self::SUCCESS_LOGIN_ROUTE]);
        return new RedirectResponse($this->urlGenerator->generate(self::SUCCESS_LOGIN_ROUTE));
    }

    // TIPS : below will be called on FORM issue only if defined ? (failing CSRF for example...)
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // TIPS : or // TODO ? onAuthenticationFailure not called, cf
        //        packages/mws-moon-manager/src/Security/MwsAuthenticationEntryPoint.php
        //        instead
        // https://openclassrooms.com/forum/sujet/symfony-custom-authentificator-authentication-47aa9
        // $auth = new AuthLogin($request->getClientIp(), $request->request->get('_username'), AuthLogin::$failed, $exception->getMessage());
        // $this->em->persist($auth);
        // $this->em->flush();
        // $response = new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        // $mwsBackUrl = $this->getTargetPath($request->getSession(), $this->firewallName);
        // $this->removeTargetPath($request->getSession(), $this->firewallName); // TIPS : clean it
        // $response->headers->set('X-Mws-Back-Url', $mwsBackUrl);
        // // dd($mwsBackUrl);
        // return $response;
        $mwsBackUrl = $request->headers->get('X-Mws-Back-Url')
        ?? $request->get('back-url')
        ?? $this->getTargetPath($request->getSession(), $this->firewallName) ?? null;

        return new RedirectResponse($this->urlGenerator->generate(
            MwsLoginFormAuthenticator::LOGIN_ROUTE, [ 
                // iterator_to_array($request->query)
                'back-url' => $mwsBackUrl ?? 
                    $this->urlGenerator->generate(
                        MwsLoginFormAuthenticator::SUCCESS_LOGIN_ROUTE
                    )
            ]
        ));
        // $subRequest = $this->request->duplicate($params, null, $attr);
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
        // return $this->urlGenerator->generate(self::LOGIN_ROUTE, [ 
        // TODO : OK ? will break and infinit loop on login if use back-ul here....
        //     // iterator_to_array($request->query)
        //     'back-url' => $request->query->get('back-url') ?? 
        //         $this->urlGenerator->generate(
        //             MwsLoginFormAuthenticator::SUCCESS_LOGIN_ROUTE
        //         )
        // ]);
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
