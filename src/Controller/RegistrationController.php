<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UsersAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mailService, JWTService $JWTService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_main');
        }

        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];

            $payload = [
                'user_id' => $user->getId()
            ];

            $token = $JWTService->generate($header, $payload, $this->getParameter('app.jwtsecret'));


            $mailService->send(
                'd38.h4ck3ur@live.fr',
                $user->getEmail(),
                'Activation de votre compte sur le site E-commerce',
                'register',
                [ 'user' => $user, 'token' => $token ]
            );

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $JWTService, UsersRepository $usersRepository, EntityManagerInterface $entityManager): Response
    {
        if($JWTService->isValid($token) && !$JWTService->isExpired($token) && $JWTService->check($token, $this->getParameter('app.jwtsecret')))
        {
            $payload = $JWTService->getPayload($token);
            $user = $usersRepository->find($payload['user_id']);
            if($user && !$user->getIsVerified())
            {
                $user->setIsVerified(true);
                $entityManager->flush($user);
                $this->addFlash('success', 'Utilisateur activé');
                return $this->redirectToRoute('profile_index');
            }
        }

        $this->addFlash('danger', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoiverif', name: 'resend_verif')]
    public function resendVerif(JWTService $JWTService, SendMailService $mailService, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();

        if(!$user)
        {
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page');
            return $this->redirectToRoute('app_login');
        }

        if($user->getIsVerified())
        {
            $this->addFlash('warning', 'Cet utilisateur est déjà activé');
            return $this->redirectToRoute('profile_index');
        }

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $payload = [
            'user_id' => $user->getId()
        ];

        $token = $JWTService->generate($header, $payload, $this->getParameter('app.jwtsecret'));


        $mailService->send(
            'd38.h4ck3ur@live.fr',
            $user->getEmail(),
            'Activation de votre compte sur le site E-commerce',
            'register',
            [ 'user' => $user, 'token' => $token ]
        );

        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('profile_index');
    }
}
