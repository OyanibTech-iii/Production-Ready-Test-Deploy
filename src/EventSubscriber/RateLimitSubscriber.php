<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\DependencyInjection\Attribute\Target;

class RateLimitSubscriber implements EventSubscriberInterface
{
    private RateLimiterFactory $globalLimiter;
    private Security $security;

    public function __construct(
        #[Target('global_limiter.limiter')] RateLimiterFactory $globalLimiter, 
        Security $security
    ) {
        $this->globalLimiter = $globalLimiter;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        if (str_starts_with($request->getPathInfo(), '/_profiler') || str_starts_with($request->getPathInfo(), '/_wdt')) {
            return;
        }

        $user = $this->security->getUser();
        $limitKey = $user ? (string) $user->getUserIdentifier() : $request->getClientIp();
        
        $limiter = $this->globalLimiter->create($limitKey);
        $limit = $limiter->consume(1);

        $request->attributes->set('_global_rate_limit', $limit);

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $limit = $request->attributes->get('_global_rate_limit');
        
        if ($limit) {
            $response->headers->set('X-RateLimit-Limit', $limit->getLimit());
            $response->headers->set('X-RateLimit-Remaining', $limit->getRemainingTokens());
            $response->headers->set('X-RateLimit-Reset', $limit->getRetryAfter()->getTimestamp());
        }
    }
}
