<?php

namespace App\Controller;

use App\Form\UsersFormType;
use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profil', name: 'profile_')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [

        ]);
    }

    #[Route('/edition', name: 'edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UsersFormType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour');
            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edition/pass', name: 'edit_password')]
    public function editPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if($request->isMethod('POST'))
        {
            $user = $this->getUser();

            if($request->request->get('pass') == $request->request->get('pass2'))
            {
                $user->setPassword($passwordHasher->hashPassword($user, $request->request->get('pass')));
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Mot de passe mis à jour avec succès');

                return $this->redirectToRoute('profile_index');
            }
            else
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas');
        }

        return $this->render('profile/editpassword.html.twig', [
        ]);
    }

    #[Route('/commandes', name: 'orders')]
    public function orders(OrdersRepository $ordersRepository, EntityManagerInterface $entityManager): Response
    {
        $orders = $ordersRepository->findBy(['users' => $this->getUser()], ['created_at' => 'DESC']);
        $ordersDetails = [];

        foreach ($orders as $order)
        {
            $dql = "SELECT od FROM App\Entity\OrdersDetails od WHERE od.orders = :orders";
            $query = $entityManager->createQuery($dql);
            $query->setParameter('orders', $order);
            $details = $query->getResult();

            $ordersDetails[$order->getId()] = $details;
        }

        return $this->render('profile/orders.html.twig', [
            'orders' => $orders,
            'ordersDetails' => $ordersDetails
        ]);
    }
}
