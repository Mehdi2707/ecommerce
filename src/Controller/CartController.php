<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrdersDetails;
use App\Entity\Products;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier', name: 'cart_')]
class CartController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(SessionInterface $session, ProductsRepository $productsRepository): Response
    {
        $panier = $session->get("panier", []);

        $dataPanier = [];
        $total = 0;

        foreach($panier as $id => $quantite)
        {
            $product = $productsRepository->find($id);
            $dataPanier[] = [
                "produit" => $product,
                "quantite" => $quantite
            ];
            $total += ($product->getPrice() /100 ) * $quantite;
        }

        return $this->render('cart/index.html.twig', [
            'dataPanier' => $dataPanier,
            'total' => $total
        ]);
    }

    #[Route('/ajouter/{id}', name: 'add')]
    public function add(Products $products, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        $id = $products->getId();

        if(!empty($panier[$id]))
            $panier[$id]++;
        else
            $panier[$id] = 1;

        $session->set("panier", $panier);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/enlever/{id}', name: 'remove')]
    public function remove(Products $products, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        $id = $products->getId();

        if(!empty($panier[$id]))
        {
            if($panier[$id] > 1)
                $panier[$id]--;
            else
                unset($panier[$id]);
        }
        else
            $panier[$id] = 1;

        $session->set("panier", $panier);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/supprimer/{id}', name: 'delete')]
    public function delete(Products $products, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        $id = $products->getId();

        if(!empty($panier[$id]))
            unset($panier[$id]);

        $session->set("panier", $panier);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/confirmation', name: 'confirm')]
    public function confirm(SessionInterface $session, ProductsRepository $productsRepository): Response
    {
        $panier = $session->get("panier", []);
        $user = $this->getUser();

        if(empty($panier))
            return $this->redirectToRoute('cart_index');

        $dataPanier = [];
        $total = 0;

        foreach($panier as $id => $quantite)
        {
            $product = $productsRepository->find($id);
            $dataPanier[] = [
                "produit" => $product,
                "quantite" => $quantite
            ];
            $total += ($product->getPrice() /100 ) * $quantite;
        }

        return $this->render('cart/confirm.html.twig' , [
            'dataPanier' => $dataPanier,
            'total' => $total,
            'user' => $user
        ]);
    }

    #[Route('/paiement', name: 'pay')]
    public function pay(SessionInterface $session, ProductsRepository $productsRepository, EntityManagerInterface $entityManager): Response
    {
        $panier = $session->get("panier", []);
        $user = $this->getUser();

        $dataPanier = [];
        $total = 0;

        foreach($panier as $id => $quantite)
        {
            $product = $productsRepository->find($id);
            $dataPanier[] = [
                "produit" => $product,
                "quantite" => $quantite
            ];
            $total += ($product->getPrice() /100 ) * $quantite;
        }

        $order = new Orders();
        $order->setUsers($user);
        $order->setReference($this->generateRandomString(6));

        $entityManager->persist($order);

        foreach($dataPanier as $product)
        {
            $orderDetails = new OrdersDetails();
            $orderDetails->setOrders($order);
            $orderDetails->setProducts($product['produit']);
            $orderDetails->setQuantity($product['quantite']);
            $orderDetails->setPrice($total * 100); // total de chaque produit !!

            $entityManager->persist($orderDetails);
        }

        $entityManager->flush();

        $session->remove("panier", []);

        $this->addFlash('success', 'Votre commande à bien été enregistrée');

        return $this->redirectToRoute('app_main');
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
