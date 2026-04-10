<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\NotFoundExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

final class NotFoundExceptionSubscriberTest extends TestCase
{
    public function testMainRequestNotFoundRendersCustom404Page(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('error/not_found.html.twig')
            ->willReturn('<html>404</html>');

        $subscriber = new NotFoundExceptionSubscriber($twig);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new NotFoundHttpException('Missing page.'),
        );

        $subscriber->onKernelException($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('<html>404</html>', $response->getContent());
    }

    public function testSubRequestNotFoundDoesNotOverrideResponse(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render');

        $subscriber = new NotFoundExceptionSubscriber($twig);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            new NotFoundHttpException('Missing page.'),
        );

        $subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testNonNotFoundExceptionIsIgnored(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render');

        $subscriber = new NotFoundExceptionSubscriber($twig);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('Boom.'),
        );

        $subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}
