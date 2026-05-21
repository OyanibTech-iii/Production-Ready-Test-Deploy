<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/course')]
#[IsGranted('ROLE_ADMIN')]
final class CourseController extends AbstractController
{
    #[Route('/new', name: 'app_admin_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('thumbnail')->getData();

            if ($imageFile instanceof UploadedFile) {
                $this->handleThumbnailUpload($imageFile, $course);
            }

            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_courses', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/course_new.html.twig', [
            'course' => $course,
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_course_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('thumbnail')->getData();

            if ($imageFile instanceof UploadedFile) {
                $this->handleThumbnailUpload($imageFile, $course, true);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_courses', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/course_edit.html.twig', [
            'course' => $course,
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Same pattern as product image upload (safe filename, move, delete old on replace); stored under public/uploads/courses.
     */
    private function handleThumbnailUpload(UploadedFile $imageFile, Course $course, bool $replaceExisting = false): void
    {
        $uploadsDir = $this->getParameter('course_images_directory');
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        if ($replaceExisting && $course->getThumbnail()) {
            $filesystem = new Filesystem();
            $old = rtrim($uploadsDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$course->getThumbnail();
            if ($filesystem->exists($old)) {
                $filesystem->remove($old);
            }
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalFilename);
        $extension = $imageFile->guessExtension() ?: strtolower((string) $imageFile->getClientOriginalExtension()) ?: 'jpg';
        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        $imageFile->move($uploadsDir, $newFilename);
        $course->setThumbnail('/uploads/courses/' . $newFilename);
    }

    #[Route('/{id}', name: 'app_admin_course_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Course $course): Response
    {
        return $this->render('admin/course_show.html.twig', [
            'course' => $course,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_course_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_courses', [], Response::HTTP_SEE_OTHER);
    }
}
