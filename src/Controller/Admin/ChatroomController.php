<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use App\Entity\Chatroom;
use App\Entity\ChatMessage;
use App\Message\ChatMessage as ChatMessageBus;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as RedisClient;
use Symfony\Component\Messenger\MessageBusInterface;

#[Route('/admin/chatroom')]
#[IsGranted('ROLE_ADMIN')]
class ChatroomController extends AbstractController
{
    #[Route('/', name: 'app_admin_chatroom')]
    public function index(UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        return $this->showChatroom('Main Admin Discussion', $userRepository, $em);
    }

    #[Route('/private/{id}', name: 'app_admin_chatroom_private')]
    public function privateChat(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $targetUser = $userRepository->find($id);
        if (!$targetUser || !in_array('ROLE_ADMIN', $targetUser->getRoles())) {
            return $this->redirectToRoute('app_admin_chatroom');
        }

        if ($targetUser === $this->getUser()) {
            return $this->redirectToRoute('app_admin_chatroom');
        }

        // Create a unique name for 1-on-1 chat to find it easily, or use a more robust way
        $userIds = [$this->getUser()->getId(), $targetUser->getId()];
        sort($userIds);
        $chatroomName = 'Private Chat: ' . implode('-', $userIds);

        return $this->showChatroom($chatroomName, $userRepository, $em, [$this->getUser(), $targetUser]);
    }

    private function showChatroom(string $name, UserRepository $userRepository, EntityManagerInterface $em, array $participants = []): Response
    {
        // Fetch all admins for the chat list
        $admins = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        // Get or create the chatroom
        $chatroomRepo = $em->getRepository(Chatroom::class);
        $chatroom = $chatroomRepo->findOneBy(['name' => $name]);
        
        if (!$chatroom) {
            $chatroom = new Chatroom();
            $chatroom->setName($name);
            
            if (empty($participants)) {
                // Default to all admins for group chat
                foreach ($admins as $admin) {
                    $chatroom->addParticipant($admin);
                }
            } else {
                foreach ($participants as $participant) {
                    $chatroom->addParticipant($participant);
                }
            }
            
            $em->persist($chatroom);
            $em->flush();
        }

        // Fetch last 50 messages
        $messages = $em->getRepository(ChatMessage::class)->findBy(
            ['chatroom' => $chatroom],
            ['sentAt' => 'ASC'],
            50
        );

        return $this->render('admin/chatroom/base.html.twig', [
            'user' => $this->getUser(),
            'admins' => $admins,
            'chatroom' => $chatroom,
            'messages' => $messages,
        ]);
    }

    #[Route('/save-message', name: 'app_admin_chatroom_save', methods: ['POST'])]
    public function saveMessage(Request $request, MessageBusInterface $bus): JsonResponse
    {
        $chatroomId = $request->request->get('chatroom_id');
        $content = $request->request->get('content');

        if (!$chatroomId || !$content) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        $bus->dispatch(new ChatMessageBus(
            (string)$content,
            ChatMessageBus::TYPE_ADMIN,
            [
                'chatroom_id' => (int)$chatroomId,
                'sender_id' => $this->getUser()->getId(),
            ]
        ));

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
