<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class LandinpageController extends AbstractController
{
    #[Route('/', name: 'app_landinpage')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        LocationRepository $locationRepository,
        EntityManagerInterface $em
    ): Response {
        return $this->render('landinpage/index.html.twig', $this->getCommonData($userRepository, $productRepository, $locationRepository, $em));
    }

    #[Route('/about', name: 'app_about')]
    public function about(
        LocationRepository $locationRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        $data = $this->getCommonData($userRepository, $productRepository, $locationRepository, $em);
        $data['page'] = 'about';

        return $this->render('landinpage/index.html.twig', $data);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(
        LocationRepository $locationRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        $data = $this->getCommonData($userRepository, $productRepository, $locationRepository, $em);
        $data['page'] = 'contacts';

        return $this->render('landinpage/index.html.twig', $data);
    }

    #[Route('/products', name: 'app_products')]
    public function products(
        LocationRepository $locationRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        $data = $this->getCommonData($userRepository, $productRepository, $locationRepository, $em);
        $data['page'] = 'products';

        return $this->render('landinpage/index.html.twig', $data);
    }

    #[Route('/services', name: 'app_services')]
    public function services(
        LocationRepository $locationRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        $data = $this->getCommonData($userRepository, $productRepository, $locationRepository, $em);
        $data['page'] = 'services';

        return $this->render('landinpage/index.html.twig', $data);
    }

    private function getCommonData(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        LocationRepository $locationRepository,
        EntityManagerInterface $em
    ): array {
        $totalUsers = $userRepository->count([]);
        $totalProducts = $productRepository->count([]);
        $products = $productRepository->findAll();
        $recentUsers = $userRepository->findBy(
            [],
            ['id' => 'DESC'],
            8
        );
        $stockTypes = $em->createQuery('SELECT DISTINCT s.stockType FROM App\Entity\Stock s')
            ->getScalarResult();
        $types = array_filter(array_column($stockTypes, 'stockType'));

        return [
            'controller_name' => 'LandinpageController',
            'total_users' => $totalUsers,
            'total_products' => $totalProducts,
            'recent_users' => $recentUsers,
            'products' => $products,
            'stock_types' => $types,
            'locations' => $locationRepository->findAll(),
            'success_rate' => 98,
        ];
    }
}
