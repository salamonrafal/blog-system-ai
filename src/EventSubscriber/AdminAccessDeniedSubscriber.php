<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

final class AdminAccessDeniedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || $event->hasResponse()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->shouldRenderAdminAccessDeniedPage($event->getThrowable(), $request)) {
            return;
        }

        $event->setResponse(new Response(
            $this->twig->render('security/access_denied.html.twig'),
            Response::HTTP_FORBIDDEN,
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -255],
        ];
    }

    private function shouldRenderAdminAccessDeniedPage(\Throwable $throwable, Request $request): bool
    {
        if (!$throwable instanceof AccessDeniedHttpException) {
            return false;
        }

        if (!in_array($request->getMethod(), [Request::METHOD_GET, Request::METHOD_HEAD], true)) {
            return false;
        }

        return str_starts_with($request->getPathInfo(), '/admin');
    }
}
