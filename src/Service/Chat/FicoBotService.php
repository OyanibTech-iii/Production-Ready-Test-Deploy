<?php

namespace App\Service\Chat;

use App\Entity\BotKnowledge;
use App\Entity\ChatLog;
use App\Repository\BotKnowledgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FicoBotService
{
    private const MSG_GEMINI_DOWN = "I'm having trouble thinking right now.";
    private const MSG_GEMINI_UNKNOWN = "I'm not sure about that yet.";

    public function __construct(
        private BotKnowledgeRepository $repo,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $geminiApiKey,
        private string $geminiModel
    ) {
    }

    public function generateResponse(string $userQuery): string
    {
        $cleanQuery = $this->normalizeQuery($userQuery);

        $knowledge = $this->resolveFromKnowledgeBase($cleanQuery);
        if ($knowledge !== null && trim($knowledge) !== '') {
            return $knowledge;
        }

        $snippets = $this->repo->findRelatedSnippetsForGemini($cleanQuery);
        
        $response = $this->getGeminiResponse($userQuery, $snippets);
        
        if ($this->isErrorMessage($response)) {
            if ($snippets !== []) {
                $response = $snippets[0]->getAnswer();
                $this->logger->info('ficoBot: Gemini failed, falling back to best snippet.', [
                    'keyword' => $snippets[0]->getKeyword()
                ]);
            }
        }

        if (!$this->isErrorMessage($response)) {
            $this->persistLearnedKnowledge($cleanQuery, $response);
        }

        $log = new ChatLog();
        $log->setQuestion($userQuery);
        $log->setAnswer($response);
        $log->setCreateAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $response;
    }

    private function isErrorMessage(string $response): bool
    {
        return \in_array($response, [self::MSG_GEMINI_DOWN, self::MSG_GEMINI_UNKNOWN], true);
    }

    private function normalizeQuery(string $userQuery): string
    {
        return preg_replace('/[?.,!]/', '', strtolower(trim($userQuery)));
    }

    private function resolveFromKnowledgeBase(string $cleanQuery): ?string
    {
        if ($cleanQuery === '') {
            return null;
        }

        $row = $this->repo->findOneBy(['keyword' => $cleanQuery]);
        if ($row) {
            return $row->getAnswer();
        }

        $row = $this->repo->createQueryBuilder('k')
            ->where(':query LIKE CONCAT(\'%\', k.keyword, \'%\')')
            ->setParameter('query', $cleanQuery)
            ->orderBy('LENGTH(k.keyword)', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row?->getAnswer();
    }

    private function getGeminiResponse(string $userPrompt, array $snippets): string
    {
        $apiKey = trim($this->geminiApiKey);
        if ($apiKey === '') {
            $this->logger->error('ficoBot: GEMINI_API_KEY is missing. Please set it in .env.local.');
            return self::MSG_GEMINI_DOWN;
        }

        $model = trim($this->geminiModel);
        if ($model === '' || $model === 'gemini_model' || str_contains($model, 'default')) {
            $model = 'gemini-1.5-flash';
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $systemPreamble = "You are ficoBot, a helpful Growfico support assistant. Answer clearly and concisely. DO NOT use any emojis or special decorative symbols in your response.\n";
        if ($snippets !== []) {
            $systemPreamble .= "Use the following internal reference when it helps; if it does not apply, answer from general knowledge while staying accurate about Growfico.\n\nReference:\n";
            foreach ($snippets as $k) {
                $kw = $k->getKeyword() ?? '';
                $ans = $this->truncateForPrompt($k->getAnswer() ?? '');
                $systemPreamble .= "- ({$kw}) {$ans}\n";
            }
            $systemPreamble .= "\n";
        }

        $parts = [];
        $parts[] = ['text' => $systemPreamble . 'User question: ' . $userPrompt];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => $parts,
                        ],
                    ],
                ],
                'timeout' => 30,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getContent(false);

            if ($status !== 200) {
                $this->logger->error('ficoBot: Gemini API returned status ' . $status, [
                    'body' => mb_substr($body, 0, 1000),
                    'model' => $model
                ]);
                return self::MSG_GEMINI_DOWN;
            }

            $data = json_decode($body, true);
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('ficoBot: Unexpected Gemini response structure', ['data' => $data]);
                return self::MSG_GEMINI_UNKNOWN;
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            return $this->stripEmojis(trim($text));
        } catch (\Throwable $e) {
            $this->logger->error('ficoBot: Gemini request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::MSG_GEMINI_DOWN;
        }
    }

    private function stripEmojis(string $text): string
    {
        // Remove emoji characters and other symbols
        return preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $text);
    }

    private function truncateForPrompt(string $text, int $maxLen = 400): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }
        return mb_substr($text, 0, $maxLen) . '…';
    }

    private function persistLearnedKnowledge(string $cleanQuery, string $response): void
    {
        if (!$this->shouldPersistLearnedResponse($response)) {
            return;
        }

        $existing = $this->repo->findOneBy(['keyword' => $cleanQuery]);
        if ($existing) {
            $existing->setAnswer($response);
            $this->entityManager->persist($existing);
            return;
        }

        $newKnowledge = new BotKnowledge();
        $newKnowledge->setKeyword($cleanQuery);
        $newKnowledge->setAnswer($response);
        $this->entityManager->persist($newKnowledge);
    }

    private function shouldPersistLearnedResponse(string $response): bool
    {
        if (trim($response) === '') {
            return false;
        }
        return !\in_array($response, [self::MSG_GEMINI_DOWN, self::MSG_GEMINI_UNKNOWN], true);
    }
}
