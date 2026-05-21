<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs')]
#[IsGranted('ROLE_ADMIN')]
class LogController extends AbstractController
{
    #[Route('/', name: 'app_admin_logs', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $activityLogRepository): Response
    {
        $qb = $activityLogRepository->createQueryBuilder('l')->orderBy('l.createdAt', 'DESC');

        // Filter out n/a records (where username is null)
        $qb->andWhere('l.username IS NOT NULL');

        if ($user = $request->query->get('user')) {
            $qb->andWhere('l.username LIKE :user')->setParameter('user', '%'.$user.'%');
        }

        if ($action = $request->query->get('action')) {
            $qb->andWhere('l.action = :action')->setParameter('action', $action);
        }

        if ($from = $request->query->get('from')) {
            $qb->andWhere('l.createdAt >= :from')->setParameter('from', new \DateTimeImmutable($from));
        }

        if ($to = $request->query->get('to')) {
            $qb->andWhere('l.createdAt <= :to')->setParameter('to', new \DateTimeImmutable($to));
        }

        $logs = $qb->setMaxResults(200)->getQuery()->getResult();

        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
            'filters' => [
                'user' => $user,
                'action' => $action,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}

