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
            $slug = $slugger->slug($category->getName())->lower();
            $category->setSlug($slug);

            // Récupération de l'entier maximum dans la colonne categoryOrder
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('MAX(c.categoryOrder)')
                ->from(Categories::class, 'c');
            $maxCategoryOrder = $queryBuilder->getQuery()->getSingleScalarResult() ?? 0;

            // Incrémentation de l'entier
            $categoryOrder = $maxCategoryOrder + 1;

            // Attribution de la valeur de l'entier à la propriété categoryOrder de l'objet Category
            $category->setCategoryOrder($categoryOrder);
            $category->setProductCount('0');

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
    public function edit(Categories $categories, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('CATEGORIES_EDIT', $categories);

        $form = $this->createForm(CategoriesFormType::class, $categories);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $slug = $slugger->slug($categories->getName())->lower();
            $categories->setSlug($slug);

            $entityManager->persist($categories);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie modifier avec succès');

            return $this->redirectToRoute('admin_categories_index');
        }

        return $this->render('admin/categories/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
