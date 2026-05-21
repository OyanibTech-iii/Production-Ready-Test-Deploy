<?php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response, File\UploadedFile};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_admin_products')]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('admin/products.html.twig', [
            'products' => $productRepository->findAll(),
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/products/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function newProduct(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $uploadsDir = $this->getParameter('images_directory');
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where images are stored
                $imageFile->move($uploadsDir, $newFilename);

                // updates the 'image' property to store the image path
                $product->setImage('/uploads/images/' . $newFilename);
            }
            if (!$product->getOwner()) {
                $product->setOwner($this->getUser());
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'CREATE_PRODUCT', sprintf('Admin created product %s', $product->getName()));

            return $this->redirectToRoute('app_admin_products', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product_new.html.twig', [
            'product' => $product,
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/products/{id}', name: 'app_admin_product_show', methods: ['GET'])]
    public function showProduct(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/products/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $uploadsDir = $this->getParameter('images_directory');
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where images are stored
                $imageFile->move($uploadsDir, $newFilename);

                // updates the 'image' property to store the image path
                $product->setImage('/uploads/images/' . $newFilename);
            }
            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'UPDATE_PRODUCT', sprintf('Admin updated product %s', $product->getName()));

            return $this->redirectToRoute('app_admin_products', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product_edit.html.twig', [
            'product' => $product,
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/products/{id}', name: 'app_admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'DELETE_PRODUCT', sprintf('Admin deleted product %s', $product->getName()));
        }

        return $this->redirectToRoute('app_admin_products', [], Response::HTTP_SEE_OTHER);
    }
}