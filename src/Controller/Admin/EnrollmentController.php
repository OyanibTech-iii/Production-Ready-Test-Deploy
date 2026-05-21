<?php

namespace App\Controller\Admin;

use App\Entity\Enrollment;
use App\Form\EnrollmentType;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/enrollment')]
class EnrollmentController extends AbstractController
{
    #[Route('/', name: 'app_admin_enrollment')]
    public function index(EnrollmentRepository $enrollmentRepository): Response
    {
        return $this->render('admin/enrollment.html.twig', [
            'enrollments' => $enrollmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_enrollment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $enrollment = new Enrollment();
        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($enrollment);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_enrollment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/enrollment_new.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_enrollment_show', methods: ['GET'])]
    public function show(Enrollment $enrollment): Response
    {
        return $this->render('admin/enrollment_show.html.twig', [
            'enrollment' => $enrollment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_enrollment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enrollment $enrollment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_enrollment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/enrollment_edit.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_enrollment_delete', methods: ['POST'])]
    public function delete(Request $request, Enrollment $enrollment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $enrollment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($enrollment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_enrollment', [], Response::HTTP_SEE_OTHER);
    }
}
