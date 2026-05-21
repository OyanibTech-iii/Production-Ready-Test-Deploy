<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stock')]
#[IsGranted('ROLE_USER')]
final class StockController extends AbstractController
{
    #[Route(name: 'app_stock_index', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        return $this->render('stock/index.html.twig', [
            'stocks' => $stockRepository->findAll(),
        ]);
    }

    // public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, StockRepository $stockRepository, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $stock = new Stock();
        $stock->setMinimumQuantity(10);
        $stock->setMaximumQuantity(500);
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Extract data for comparison
            $selectedProducts = $stock->getProducts()->toArray();
            $type = $stock->getStockType();
            $location = $stock->getLocationRel();
            $newQuantity = $stock->getQuantity();

            // 1. Check for existing stock matching Product + Type + Location
            $existingStock = $stockRepository->findExistingStock(
                $selectedProducts,
                $type,
                $location
            );

            if ($existingStock) {
                // REDUNDANCY PREVENTED: Update existing row instead of creating new
                $existingStock->setQuantity($existingStock->getQuantity() + $newQuantity);
                $existingStock->setUpdatedAt(new \DateTime());

                $activeStock = $existingStock; // Use this for the logger below
                $flashMessage = sprintf('Updated existing stock. Added %d to current quantity.', $newQuantity);
                $logAction = 'UPDATE_STOCK';
            } else {
                // NO REDUNDANCY: Create new record
                $stock->setCreatedAt(new \DateTime());
                $stock->setUpdatedAt(new \DateTime());
                $stock->setOwner($this->getUser());
                $entityManager->persist($stock);

                $activeStock = $stock; // Use this for the logger below
                $flashMessage = 'New stock record created successfully.';
                $logAction = 'CREATE_STOCK';
            }

            $entityManager->flush();

            $activityLogger->log(
                $this->getUser(),
                $logAction,
                sprintf('%s with ID %d (Quantity: %d)', $logAction, $activeStock->getId(), $activeStock->getQuantity())
            );

            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }
    #[Route('/prefill', name: 'app_stock_prefill', methods: ['GET'])]
    public function stockPrefill(Request $request, StockRepository $stockRepository): JsonResponse
    {
        $productId = (int) $request->query->get('productId', 0);

        if ($productId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid product id'], 400);
        }

        $stock = $stockRepository->findLatestForProduct($productId);

        if (!$stock) {
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'quantity' => null,
                    'minimumQuantity' => null,
                    'maximumQuantity' => null,
                ],
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'quantity' => $stock->getQuantity(),
                'minimumQuantity' => $stock->getMinimumQuantity(),
                'maximumQuantity' => $stock->getMaximumQuantity(),
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_stock_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_STAFF')]
    public function show(Stock $stock): Response
    {
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('STOCK_EDIT', $stock);

        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update timestamp
            $stock->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            // Log activity
            $activityLogger->log($this->getUser(), 'UPDATE_STOCK', sprintf('Updated stock with ID %d', $stock->getId()));

            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('STOCK_DELETE', $stock);

        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->getPayload()->getString('_token'))) {
            // Log activity before deletion
            $activityLogger->log($this->getUser(), 'DELETE_STOCK', sprintf('Deleted stock with ID %d', $stock->getId()));

            $entityManager->remove($stock);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
