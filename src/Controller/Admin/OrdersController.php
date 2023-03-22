<?php

namespace App\Controller\Admin;

use App\Repository\OrdersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commandes', name: 'admin_orders_')]
class OrdersController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function orders(OrdersRepository $ordersRepository, EntityManagerInterface $entityManager): Response
    {
        $orders = $ordersRepository->findBy([], ['created_at' => 'DESC']);
        $ordersDetails = [];

        foreach ($orders as $order)
        {
            $dql = "SELECT od FROM App\Entity\OrdersDetails od WHERE od.orders = :orders";
            $query = $entityManager->createQuery($dql);
            $query->setParameter('orders', $order);
            $details = $query->getResult();

            $ordersDetails[$order->getId()] = $details;
        }

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'ordersDetails' => $ordersDetails
        ]);
    }
}