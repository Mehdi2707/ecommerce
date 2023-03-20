<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
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
}
