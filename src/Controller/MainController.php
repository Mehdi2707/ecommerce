<?php

namespace App\Controller;

use App\Repository\CategoriesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(CategoriesRepository $categoriesRepository, ProductsRepository $productsRepository): Response
    {
        $products = $productsRepository->findAll();

        $categories = $categoriesRepository->findBy([], ['categoryOrder' => 'asc']);

        foreach ($categories as $category) {
            $productCount = $productsRepository->countByCategoryId($category->getId());
            $category->setProductCount($productCount);
        }

        return $this->render('main/index.html.twig', [
            'categories' => $categories,
            'products' => $products
        ]);
    }
}
