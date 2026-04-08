<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AdminAccessDeniedSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

final class AdminAccessDeniedSubscriberTest extends TestCase
{
    public function testAdminGetRequestRendersAccessDeniedPage(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('security/access_denied.html.twig')
            ->willReturn('<html>403</html>');

        $subscriber = new AdminAccessDeniedSubscriber($twig);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/admin'),
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException('Access Denied.'),
        );

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('<html>403</html>', $response->getContent());
    }

    public function testNonAdminPathIsIgnored(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render');

        $subscriber = new AdminAccessDeniedSubscriber($twig);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/login'),
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException('Access Denied.'),
        );

        $subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}
