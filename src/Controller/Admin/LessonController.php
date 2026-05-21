<?php

namespace App\Controller\Admin;

use App\Entity\Lesson;
use App\Form\LessonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/admin/lesson')]
class LessonController extends AbstractController
{
    private const ALLOWED_CONTENT_EXTENSIONS = ['twig', 'html', 'htm', 'pdf'];

    #[Route(name: 'app_admin_lesson_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirect($this->generateUrl('app_admin_courses').'#lessons-overview');
    }

    #[Route('/new', name: 'app_admin_lesson_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson, ['enable_content_upload' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->applyUploadedLessonFile($form, $lesson)) {
                return $this->render('admin/lesson/new.html.twig', [
                    'lesson' => $lesson,
                    'form' => $form,
                ]);
            }
            if (!$this->lessonHasDisplayableBody($lesson)) {
                $form->addError(new FormError('Add text content or upload a .twig, .html, or .pdf file.'));

                return $this->render('admin/lesson/new.html.twig', [
                    'lesson' => $lesson,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirect($this->generateUrl('app_admin_courses').'#lessons-overview');
        }

        return $this->render('admin/lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        return $this->render('admin/lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_lesson_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonType::class, $lesson, ['enable_content_upload' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->applyUploadedLessonFile($form, $lesson)) {
                return $this->render('admin/lesson/edit.html.twig', [
                    'lesson' => $lesson,
                    'form' => $form,
                ]);
            }
            if (!$this->lessonHasDisplayableBody($lesson)) {
                $form->addError(new FormError('Add text content or upload a .twig, .html, or .pdf file.'));

                return $this->render('admin/lesson/edit.html.twig', [
                    'lesson' => $lesson,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            return $this->redirect($this->generateUrl('app_admin_courses').'#lessons-overview');
        }

        return $this->render('admin/lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_lesson_delete', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->getPayload()->getString('_token'))) {
            $this->removeStoredLessonPdf($lesson);
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirect($this->generateUrl('app_admin_courses').'#lessons-overview');
    }

    /**
     * @return bool true if OK; false if validation error was added to the form
     */
    private function applyUploadedLessonFile(FormInterface $form, Lesson $lesson): bool
    {
        if (!$form->has('contentUpload')) {
            return true;
        }

        $upload = $form->get('contentUpload')->getData();
        if (!$upload instanceof UploadedFile) {
            return true;
        }

        $ext = strtolower(pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_CONTENT_EXTENSIONS, true)) {
            $form->get('contentUpload')->addError(new FormError('Only .twig, .html, and .pdf files are allowed.'));

            return false;
        }

        if (in_array($ext, ['twig', 'html', 'htm'], true)) {
            $text = @file_get_contents($upload->getPathname());
            if ($text === false) {
                $form->get('contentUpload')->addError(new FormError('Could not read the file.'));

                return false;
            }
            $lesson->setContent($text);
            $this->removeStoredLessonPdf($lesson);
            $lesson->setContentFile(null);

            return true;
        }

        $this->storePdfLessonFile($upload, $lesson);

        return true;
    }

    private function lessonHasDisplayableBody(Lesson $lesson): bool
    {
        if ($lesson->getContentFile()) {
            return true;
        }

        return trim((string) $lesson->getContent()) !== '';
    }

    private function storePdfLessonFile(UploadedFile $upload, Lesson $lesson): void
    {
        $dir = (string) $this->getParameter('lesson_content_directory');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $previous = $lesson->getContentFile();
        $newFilename = $this->computeSafePdfFilename($upload);
        $upload->move($dir, $newFilename);
        $lesson->setContentFile($newFilename);
        $this->deleteLessonPdfFileFromDisk($previous);
    }

    private function computeSafePdfFilename(UploadedFile $upload): string
    {
        $originalFilename = pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9-_]/', '_', (string) $originalFilename);

        return $safeFilename.'-'.uniqid('', true).'.pdf';
    }

    private function deleteLessonPdfFileFromDisk(?string $filename): void
    {
        if (!$filename) {
            return;
        }
        $dir = rtrim((string) $this->getParameter('lesson_content_directory'), DIRECTORY_SEPARATOR);
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        $filesystem = new Filesystem();
        if ($filesystem->exists($path)) {
            $filesystem->remove($path);
        }
    }

    private function removeStoredLessonPdf(Lesson $lesson): void
    {
        $this->deleteLessonPdfFileFromDisk($lesson->getContentFile());
        $lesson->setContentFile(null);
    }
}
