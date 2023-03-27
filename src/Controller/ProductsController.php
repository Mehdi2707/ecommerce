<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produits', name: 'products_')]
class ProductsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('products/index.html.twig', [
            'controller_name' => 'ProductsController',
        ]);
    }

    #[Route('/{slug}', name: 'details')]
    public function details(Products $products, ProductsRepository $productsRepository): Response
    {
        $categoryId = $products->getCategories();
        $productsOfCategory = $productsRepository->findBy(['categories' => $categoryId], []);

        return $this->render('products/details.html.twig', [
            'product' => $products,
            'products' => $productsOfCategory,
        ]);
    }
}
