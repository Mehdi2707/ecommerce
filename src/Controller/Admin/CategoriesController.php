<?php

namespace App\Controller\Admin;

use App\Entity\Categories;
use App\Form\CategoriesFormType;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categories', name: 'admin_categories_')]
class CategoriesController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CategoriesRepository $categoriesRepository):Response
    {
        $categories = $categoriesRepository->findBy([], ['categoryOrder' => 'asc']);
        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/ajout', name: 'add')]
    public function add(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = new Categories();

        $form = $this->createForm(CategoriesFormType::class, $category);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $slug = $slugger->slug($category->getName());
            $category->setSlug($slug);

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie ajouté avec succès');

            return $this->redirectToRoute('admin_categories_index');
        }

        return $this->render('admin/categories/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edition/{id}', name: 'edit')]
    public function edit(Products $products, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, PictureService $pictureService): Response
    {
//        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $products);
//
////        $prix = $products->getPrice() / 100;
////        $products->setPrice($prix);
//
//        $productForm = $this->createForm(ProductsFormType::class, $products);
//
//        $productForm->handleRequest($request);
//
//        if($productForm->isSubmitted() && $productForm->isValid())
//        {
//            $images = $productForm->get('images')->getData();
//
//            foreach($images as $image)
//            {
//                $folder = 'products';
//
//                $fichier = $pictureService->add($image, $folder, 300, 300);
//
//                $img = new Images();
//                $img->setName($fichier);
//                $products->addImage($img);
//            }
//
//            $slug = $slugger->slug($products->getName());
//            $products->setSlug($slug);
//
////            $prix = $products->getPrice() * 100;
////            $products->setPrice($prix);
//
//            $entityManager->persist($products);
//            $entityManager->flush();
//
//            $this->addFlash('success', 'Produit modifier avec succès');
//
//            return $this->redirectToRoute('admin_products_index');
//        }

        return $this->render('admin/categories/edit.html.twig', [
//            'productForm' => $productForm->createView(),
//            'product' => $products
        ]);
    }

}