<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\SecurityController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityControllerTest extends TestCase
{
    public function testLoginRedirectsAdministratorToDashboard(): void
    {
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $authenticationUtils
            ->expects($this->never())
            ->method('getLastUsername');
        $authenticationUtils
            ->expects($this->never())
            ->method('getLastAuthenticationError');

        $controller = new TestSecurityController();
        $controller->grantedRoles['ROLE_ADMIN'] = true;

        $response = $controller->login($authenticationUtils);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testLoginRendersLoginTemplateForNonAdministrator(): void
    {
        $error = new AuthenticationException('Invalid credentials.');
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $authenticationUtils
            ->expects($this->once())
            ->method('getLastUsername')
            ->willReturn('admin@example.com');
        $authenticationUtils
            ->expects($this->once())
            ->method('getLastAuthenticationError')
            ->willReturn($error);

        $controller = new TestSecurityController();

        $response = $controller->login($authenticationUtils);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('security/login.html.twig', $controller->capturedView);
        $this->assertSame('admin@example.com', $controller->capturedParameters['last_username']);
        $this->assertSame($error, $controller->capturedParameters['error']);
    }

    public function testLogoutThrowsFrameworkHandledLogicException(): void
    {
        $controller = new TestSecurityController();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Logout is handled by Symfony security.');

        $controller->logout();
    }
}

final class TestSecurityController extends SecurityController
{
    /** @var array<string, bool> */
    public array $grantedRoles = [];

    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    public function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        return $this->grantedRoles[(string) $attribute] ?? false;
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/admin', $status);
    }
}
