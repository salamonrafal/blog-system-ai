<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\LoginSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class LoginSuccessHandlerTest extends TestCase
{
    public function testRedirectsAdministratorToDashboard(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('admin_dashboard')
            ->willReturn('/admin');

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getRoleNames')
            ->willReturn(['ROLE_ADMIN']);

        $handler = new LoginSuccessHandler($router);
        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testRedirectsRegularUserToHomepage(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('blog_index')
            ->willReturn('/');

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getRoleNames')
            ->willReturn(['ROLE_USER']);

        $handler = new LoginSuccessHandler($router);
        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }
}
