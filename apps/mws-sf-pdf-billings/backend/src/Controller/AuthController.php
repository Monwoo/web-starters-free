<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends ApiController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Test with :
     * 
     * TODO doc (curl cmds)
     * 
     * @Route("/api/register", name="register", methods={"POST"})
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordHasherInterface $encoder): JsonResponse
    {
        // https://symfony.com/doc/5.4/security/passwords.html
        $request = $this->transformJsonBody($request);
        $username = $request->get('username');
        $password = $request->get('password');
        $email = $request->get('email');

        if (empty($username) || empty($password) || empty($email)) {
            return $this->respondValidationError("Invalid Username or Password or Email");
        }


        $user = new User($username);
        // https://www.reddit.com/r/symfony/comments/o5y7xf/how_to_update_registrationformcontroller_for_53/
        $user->setPassword($encoder->hashPassword($user, $password));
        $user->setEmail($email);
        $user->setUsername($username);
        $this->em->persist($user);
        $this->em-> flush();
        return $this->respondWithSuccess(sprintf('User %s successfully created', $user->getUsername()));
    }

    /**
     * @Route("/api/login_check", name="login-check", methods={"POST"})
     * @param User $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function getTokenUser(User $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }
}
