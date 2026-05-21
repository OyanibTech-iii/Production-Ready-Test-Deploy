<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

class ErrorController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        // Injecting the logger to track these errors
        $this->logger = $logger;
    }
    public function show(\Throwable $exception, Request $request): Response
    {
        // Check if this is an admin or user page route (by route name or path)
        $routeName = $request->attributes->get('_route');
        $pathInfo = $request->getPathInfo();
        
        $isAdminRoute = ($routeName && strpos($routeName, 'app_admin_') === 0) || 
                        strpos($pathInfo, '/admin') === 0;
        
        $isUserRoute = ($routeName && strpos($routeName, 'app_user_page_') === 0) || 
                       strpos($pathInfo, '/user') === 0;
        
        if ($exception instanceof NotFoundHttpException) {
            $this->logger->error('Page not found: ' . $exception->getMessage());
            
            if ($isAdminRoute) {
                $template = 'bundles/TwigBundle/Exception/Admin.html.twig';
            } elseif ($isUserRoute) {
                $template = 'bundles/TwigBundle/Exception/User.html.twig';
            } else {
                $template = 'bundles/TwigBundle/Exception/Error.html.twig';
            }
            
            return $this->render($template, [
                'message' => 'The requested page could not be located.',
                'status_code' => 404,
                'status_text' => 'Not Found',
                'exception' => $exception
            ]);
        }

        if ($exception instanceof AccessDeniedHttpException) {
            if ($isAdminRoute) {
                $template = 'bundles/TwigBundle/Exception/Admin.html.twig';
            } elseif ($isUserRoute) {
                $template = 'bundles/TwigBundle/Exception/User.html.twig';
            } else {
                $template = 'bundles/TwigBundle/Exception/Error403.html.twig';
            }
            
            return $this->render($template, [
                'message' => 'You do not have permission to access this resource.',
                'status_code' => 403,
                'status_text' => 'Access Denied',
                'exception' => $exception
            ]);
        }

        $this->logger->critical('Critical System Error: ' . $exception->getMessage());
        
        if ($isAdminRoute) {
            $template = 'bundles/TwigBundle/Exception/Admin.html.twig';
        } elseif ($isUserRoute) {
            $template = 'bundles/TwigBundle/Exception/User.html.twig';
        } else {
            $template = 'bundles/TwigBundle/Exception/Error.html.twig';
        }
        
        return $this->render($template, [
            'message' => 'Something went wrong on our end.',
            'status_code' => 500,
            'status_text' => 'Internal Server Error',
            'exception' => $exception
        ]);
    }
}