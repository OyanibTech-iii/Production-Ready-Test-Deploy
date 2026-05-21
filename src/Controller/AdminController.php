<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{

    #[Route('/settings', name: 'app_admin_settings')]
    public function settings(): Response
    {
        return $this->render('admin/settings.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/courses', name: 'app_admin_courses')]
    public function courses(CourseRepository $courseRepository, LessonRepository $lessonRepository): Response
    {
        return $this->render('admin/courses.html.twig', [
            'user' => $this->getUser(),
            'courses' => $courseRepository->findBy([], ['id' => 'DESC']),
            'lessons' => $lessonRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/courses/air-layering', name: 'app_admin_course_airlayering')]
    public function courseAirLayering(): Response
    {
        return $this->render('admin/course_airlayering.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/help', name: 'app_admin_help')]
    public function help(): Response
    {
        return $this->render('admin/help.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/self-service', name: 'app_admin_self_service')]
    public function selfService(): Response
    {
        return $this->render('admin/self_service.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/chatsupport', name: 'app_admin_chat_support')]
    public function chatSupport(): Response
    {
        return $this->render('admin/help_support.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/deleteStock/{id}', name: 'app_admin_delete_stock')]
    public function deleteStock($id): Response
    {
        return $this->render('admin/stock_delete.html.twig', [
            'stockId' => $id,
        ]);
    }

    #[Route('/notifications', name: 'app_admin_notification')]
    public function notifications(NotificationRepository $notificationRepository): Response
    {
        return $this->render('admin/notification.html.twig', [
            'notifications' => $notificationRepository->findBy([], ['createAt' => 'DESC']),
        ]);
    }

    #[Route('/notifications/clear', name: 'app_admin_notification_clear', methods: ['POST'])]
    public function clearNotifications(Request $request, NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('clear_notifications', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $notifications = $notificationRepository->findAll();
        foreach ($notifications as $notification) {
            $entityManager->remove($notification);
        }
        $entityManager->flush();

        $this->addFlash('success', 'All notifications have been cleared.');

        return $this->redirectToRoute('app_admin_notification');
    }

    #[Route('/notifications/read-all', name: 'app_admin_notification_read_all', methods: ['POST'])]
    public function markAllAsRead(Request $request, NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('read_all_notifications', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $notifications = $notificationRepository->findBy(['isRead' => false]);
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }
        $entityManager->flush();

        $this->addFlash('success', 'All notifications have been marked as read.');

        return $this->redirectToRoute('app_admin_notification');
    }
}