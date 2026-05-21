<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageController extends AbstractController
{
    #[Route('/api/uploads/images/{filename}', name: 'api_serve_image', methods: ['GET'])]
    public function serveImage(string $filename, ParameterBagInterface $params): Response
    {
        $imageDir = $params->get('images_directory');
        $filePath = $imageDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Image not found.');
        }

        return new BinaryFileResponse($filePath);
    }

    #[Route('/api/uploads/{folder}/{filename}', name: 'api_serve_upload', methods: ['GET'])]
    public function serveUpload(string $folder, string $filename, ParameterBagInterface $params): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found.');
        }

        return new BinaryFileResponse($filePath);
    }
}
