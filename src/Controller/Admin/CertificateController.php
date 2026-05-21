<?php

namespace App\Controller\Admin;

use App\Entity\Certificate;
use App\Form\CertificateType;
use App\Repository\CertificateRepository;
use App\Service\CertificateEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/certificate')]
final class CertificateController extends AbstractController
{
    #[Route(name: 'app_admin_certificate', methods: ['GET'])]
    public function index(CertificateRepository $certificateRepository): Response
    {
        return $this->render('admin/certificate.html.twig', [
            'certificates' => $certificateRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_certificate_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $certificate = new Certificate();
        $form = $this->createForm(CertificateType::class, $certificate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($certificate);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_certificate', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/certificate_new.html.twig', [
            'certificate' => $certificate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_certificate_show', methods: ['GET'])]
    public function show(Certificate $certificate): Response
    {
        return $this->render('admin/certificate_show.html.twig', [
            'certificate' => $certificate,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_certificate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Certificate $certificate, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CertificateType::class, $certificate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_certificate', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/certificate_edit.html.twig', [
            'certificate' => $certificate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_certificate_delete', methods: ['POST'])]
    public function delete(Request $request, Certificate $certificate, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$certificate->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($certificate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_certificate', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/send-email', name: 'app_admin_certificate_send_email', methods: ['POST'])]
    public function sendCertificateEmail(
        Request $request,
        Certificate $certificate,
        CertificateEmailService $certificateEmailService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $certificateId = $certificate->getId();
        if ($certificateId === null) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid certificate.'], 400);
        }

        $csrfTokenId = 'send_certificate_pdf_' . $certificateId;
        $submittedCsrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken($csrfTokenId, $submittedCsrfToken))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 400);
        }

        $pdfFile = $request->files->get('pdf');
        if (!$pdfFile instanceof UploadedFile) {
            return new JsonResponse(['success' => false, 'message' => 'Missing PDF upload.'], 400);
        }

        try {
            $pdfBinary = $pdfFile->getContent();
            $pdfFilename = sprintf('Growfico-Certificate-%s.pdf', $certificate->getCertificateCode() ?? $certificateId);
            $certificateEmailService->sendCertificatePdfToUser($certificate, $pdfBinary, $pdfFilename);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to send certificate email.',
            ], 500);
        }
    }

    #[Route('/{id}', name: 'app_admin_certificate_template', methods: ['GET'])]
    public function viewTemplate(Certificate $certificate): Response
    {
        return $this->render('admin/certificate_template.html.twig', [
            'certificate' => $certificate,
        ]);
    }
}
