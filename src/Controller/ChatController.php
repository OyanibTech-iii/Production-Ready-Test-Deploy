<?php
namespace App\Controller;

use App\Message\ChatMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ChatController extends AbstractController
{
    #[Route('/chat-api', name: 'app_chat_api', methods: ['POST'])]

    public function ask(Request $request, MessageBusInterface $bus, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $question = $data['message'] ?? $request->request->get('message', '');

            if (!$question) {
                return new JsonResponse(['answer' => "Please provide a message."]);
            }

            $envelope = $bus->dispatch(new ChatMessage((string)$question));

            $handledStamp = $envelope->last(HandledStamp::class);
            $answer = $handledStamp ? $handledStamp->getResult() : "I'm thinking...";

            return new JsonResponse(['answer' => $answer]);
        } catch (\Throwable $e) {
            $logger->error('chat-api failed: ' . $e->getMessage(), ['exception' => $e]);

            return new JsonResponse([
                'answer' => "I'm having trouble thinking right now.",
            ]);
        }
    }

    #[Route('/chat', name: 'app_chat_gui')]
    public function gui() {
        return $this->render('chat/index.html.twig');
    }
}