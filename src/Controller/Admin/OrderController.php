<?php
namespace App\Controller\Admin;

use App\Entity\{Order, OrderItem, Notification};
use App\Form\OrderType;
use App\Repository\{OrderRepository, OrderItemRepository, ProductRepository};
use App\Service\NotificationService;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/orders')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_admin_order', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('admin/order.html.twig', [
            'orders' => $orderRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, NotificationService $notificationService): Response
    {
        $order = new Order();
        $order->addItem(new OrderItem());
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getStatus() === Order::STATUS_COMPLETED) {
                $this->decrementOrderStock($order);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            // Create notification for the current admin user
            $notificationService->create(
                $this->getUser(),
                'New Order Created',
                'Order #' . $order->getId() . ' has been created.',
                'order',
                'medium'
            );

            return $this->redirectToRoute('app_admin_order', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/order_new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    private function decrementOrderStock(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity() ?? 0;

            if (!$product || $quantity <= 0) {
                continue;
            }

            if ($quantity > $product->getCurrentStockQuantity()) {
                continue;
            }

            $product->decrementStock($quantity);
        }
    }

    private function restoreOrderStock(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity() ?? 0;

            if (!$product || $quantity <= 0) {
                continue;
            }

            $product->incrementStock($quantity);
        }
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order_show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, NotificationService $notificationService): Response
    {
        $originalStatus = $order->getStatus();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($originalStatus !== Order::STATUS_COMPLETED && $order->getStatus() === Order::STATUS_COMPLETED) {
                $this->decrementOrderStock($order);
            }

            if ($originalStatus === Order::STATUS_COMPLETED && $order->getStatus() === Order::STATUS_CANCELLED) {
                $this->restoreOrderStock($order);
            }

            $entityManager->flush();

            // Create notification for the current admin user if status changed to completed
            if ($originalStatus !== Order::STATUS_COMPLETED && $order->getStatus() === Order::STATUS_COMPLETED) {
                $notificationService->create(
                    $this->getUser(),
                    'Order Completed',
                    'Order #' . $order->getId() . ' has been marked as completed.',
                    'order',
                    'high'
                );
            }

            return $this->redirectToRoute('app_admin_order', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/order_edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_order', [], Response::HTTP_SEE_OTHER);
    }
}