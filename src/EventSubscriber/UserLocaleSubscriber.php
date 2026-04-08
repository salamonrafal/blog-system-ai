<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\UserLanguageResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class UserLocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserLanguageResolver $userLanguageResolver)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $request->setLocale($this->userLanguageResolver->resolveLanguage($request));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
