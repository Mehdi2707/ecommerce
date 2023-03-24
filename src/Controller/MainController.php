<?php

namespace App\Controller;

use App\Repository\CategoriesRepository;
use App\Repository\ProductsRepository;
use App\Service\SendMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, SendMailService $mailService): Response
    {
        if($request->isMethod('POST'))
        {
            $mailService->send(
                $request->get('email'),
                $this->getParameter('app.mailaddress'),
                $request->get('subject'),
                'contact',
                [ 'name' => $request->get('name'), 'message' => $request->get('message')]
            );
            return new Response('OK', Response::HTTP_OK);
        }

        return $this->render('main/contact.html.twig', [
        ]);
    }

    public function nav(CategoriesRepository $categoriesRepository, SessionInterface $session): Response
    {
        $panier = $session->get("panier", []);
        $quantitePanier = 0;

        $categories = $categoriesRepository->findBy([], ['categoryOrder' => 'asc']);
        $newCategories = [];

        foreach($panier as $quantite)
        {
            $quantitePanier += $quantite;
        }

        foreach ($categories as $category) {
            // Si la catégorie n'a pas de parent
            if ($category->getParent() === null) {
                // Ajouter la catégorie au nouveau tableau
                $newCategories[] = $category;
            } else {
                // Trouver l'index du parent dans le nouveau tableau
                $parentIndex = array_search($category->getParent(), $newCategories);

                // Si le parent n'a pas encore été ajouté, ajouter le parent au nouveau tableau
                if ($parentIndex === false) {
                    $newCategories[] = $category->getParent();
                    $parentIndex = count($newCategories) - 1;
                }

                // Ajouter la catégorie comme sous-catégorie du parent
                $newCategories[$parentIndex]->addCategory($category);
            }
        }

        return $this->render('_partials/_nav.html.twig', [
            'quantitePanier' => $quantitePanier,
            'categories' => $newCategories,
        ]);
    }
}
