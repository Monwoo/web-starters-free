<?php
// TODO ?
namespace MWS\MoonManagerBundle\Security\Authentication;

class __AuthenticationSuccessHandler {
}

// https://symfony.com/doc/current/security.html#customize-successful-and-failed-authentication-behavior
// https://symfony.com/doc/7.1/security/login_link.html#login-link_customize-success-handler
// namespace App\Security\Authentication;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

// class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
// {
//     public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
//     {
//         $user = $token->getUser();
//         $userApiToken = $user->getApiToken();

//         return new JsonResponse(['apiToken' => $userApiToken]);
//     }
// }