<?php
namespace App\Controller\Admin;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\{StockRepository, LocationRepository};
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/stocks')]
class StockController extends AbstractController
{
    #[Route('/', name: 'app_admin_stocks')]
    public function index(StockRepository $stockRepository, LocationRepository $locationRepository): Response
    {
        return $this->render('admin/stocks.html.twig', [
            'stocks' => $stockRepository->findAll(),
            'locations' => $locationRepository->findBy([], ['name' => 'ASC']),
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/stocks/prefill', name: 'app_admin_stock_prefill', methods: ['GET'])]
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
    #[Route('/stocks/new', name: 'app_admin_stock_new', methods: ['GET', 'POST'])]
    public function newStock(Request $request, EntityManagerInterface $entityManager, StockRepository $stockRepository, ActivityLogger $activityLogger): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Check for existing record (Product + Type + Location)
            $existingStock = $stockRepository->findExistingStock(
                $stock->getProducts()->toArray(),
                $stock->getStockType(),
                $stock->getLocationRel()
            );

            if ($existingStock) {
                // DUPLICATE DETECTED: Update existing row quantity instead of creating new
                $existingStock->setQuantity($existingStock->getQuantity() + $stock->getQuantity());
                $existingStock->setUpdatedAt(new \DateTime());

                // Use existing stock for logging
                $activeStock = $existingStock;
                $this->addFlash('success', 'Existing stock matched. Quantity updated.');
            } else {
                // NO DUPLICATE: Create new record
                $stock->setCreatedAt(new \DateTime());
                $stock->setUpdatedAt(new \DateTime());
                $entityManager->persist($stock);

                $activeStock = $stock;
                $this->addFlash('success', 'New stock record created.');
            }

            $entityManager->flush();

            $activityLogger->log(
                $this->getUser(),
                'STOCK_ACTION',
                sprintf('Managed stock for %s at %s', $activeStock->getStockType(), $activeStock->getLocationRel())
            );

            return $this->redirectToRoute('app_admin_stocks');
        }

        return $this->render('admin/stock_new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/stocks/check-exists', name: 'app_admin_stock_check', methods: ['POST'])]
    public function checkExists(Request $request, StockRepository $stockRepository): JsonResponse
    {
        $params = json_decode($request->getContent(), true);

        // Logic to find stock by IDs (simplified version of your repository method)
        $match = $stockRepository->findOneBy([
            'stockType' => $params['type'],
            'location' => $params['location']
        ]);

        // Optional: Add logic here to verify the Product IDs match exactly if using many-to-many

        return new JsonResponse(['exists' => $match !== null]);
    }

    #[Route('/stocks/{id}', name: 'app_admin_stock_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showStock(Stock $stock): Response
    {
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/stocks/{id}/edit', name: 'app_admin_stock_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editStock(Request $request, Stock $stock, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update timestamp
            $stock->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'UPDATE_STOCK', sprintf('Admin updated stock (Type: %s, Location: %s)', $stock->getStockType(), $stock->getLocationRel()));

            return $this->redirectToRoute('app_admin_stocks', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/stock_edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/stocks/{id}', name: 'app_admin_stock_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteStock(Request $request, Stock $stock, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($stock);
            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'DELETE_STOCK', sprintf('Admin deleted stock (Type: %s, Location: %s)', $stock->getStockType(), $stock->getLocationRel()));
        }

        return $this->redirectToRoute('app_admin_stocks', [], Response::HTTP_SEE_OTHER);
    }
}