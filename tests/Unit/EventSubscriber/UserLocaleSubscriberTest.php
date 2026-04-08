<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\UserLocaleSubscriber;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class UserLocaleSubscriberTest extends TestCase
{
    public function testMainRequestLocaleMatchesSelectedUserLanguage(): void
    {
        $request = new Request(cookies: ['user_language' => 'pl']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $subscriber = new UserLocaleSubscriber(new UserLanguageResolver($requestStack));
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $subscriber->onKernelRequest($event);

        $this->assertSame('pl', $request->getLocale());
    }

    public function testSubRequestDoesNotOverrideLocale(): void
    {
        $request = new Request(cookies: ['user_language' => 'pl']);
        $request->setLocale('en');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $subscriber = new UserLocaleSubscriber(new UserLanguageResolver($requestStack));
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
        );

        $subscriber->onKernelRequest($event);

        $this->assertSame('en', $request->getLocale());
    }
}
