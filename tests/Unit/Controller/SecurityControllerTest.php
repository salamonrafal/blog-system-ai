<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\SecurityController;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
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

        $response = $controller->login($authenticationUtils, $this->createUserLanguageResolver('pl'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testLoginRendersLoginTemplateForNonAdministrator(): void
    {
        $error = new InvalidCredentialsAuthenticationException();
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

        $response = $controller->login($authenticationUtils, $this->createUserLanguageResolver('pl'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('security/login.html.twig', $controller->capturedView);
        $this->assertSame('admin@example.com', $controller->capturedParameters['last_username']);
        $this->assertSame($error, $controller->capturedParameters['error']);
        $this->assertSame('Nieprawidłowe dane logowania.', $controller->capturedParameters['error_message']);
    }

    public function testLoginRedirectsAuthenticatedNonAdministratorToHomepage(): void
    {
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $authenticationUtils
            ->expects($this->never())
            ->method('getLastUsername');
        $authenticationUtils
            ->expects($this->never())
            ->method('getLastAuthenticationError');

        $controller = new TestSecurityController();
        $controller->mockUser = $this->createMock(UserInterface::class);

        $response = $controller->login($authenticationUtils, $this->createUserLanguageResolver('pl'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testLogoutThrowsFrameworkHandledLogicException(): void
    {
        $controller = new TestSecurityController();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Logout is handled by Symfony security.');

        $controller->logout();
    }

    public function testAccessDeniedRendersAccessDeniedTemplate(): void
    {
        $controller = new TestSecurityController();

        $response = $controller->accessDenied();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('security/access_denied.html.twig', $controller->capturedView);
    }

    public function testLoginTranslatesMissingAdministratorRoleError(): void
    {
        $error = new MissingAdministratorRoleAuthenticationException();
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $authenticationUtils
            ->expects($this->once())
            ->method('getLastUsername')
            ->willReturn('user@example.com');
        $authenticationUtils
            ->expects($this->once())
            ->method('getLastAuthenticationError')
            ->willReturn($error);

        $controller = new TestSecurityController();

        $response = $controller->login($authenticationUtils, $this->createUserLanguageResolver('pl'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('To konto nie ma dostępu administratora.', $controller->capturedParameters['error_message']);
    }

    private function createUserLanguageResolver(string $language): UserLanguageResolver
    {
        $resolver = $this->createMock(UserLanguageResolver::class);
        $resolver
            ->method('translate')
            ->willReturnCallback(static fn (string $polish, string $english): string => 'pl' === $language ? $polish : $english);

        return $resolver;
    }
}

final class InvalidCredentialsAuthenticationException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Invalid credentials.';
    }
}

final class MissingAdministratorRoleAuthenticationException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Administrator access is required.';
    }
}

final class TestSecurityController extends SecurityController
{
    /** @var array<string, bool> */
    public array $grantedRoles = [];

    public ?UserInterface $mockUser = null;

    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    public function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        return $this->grantedRoles[(string) $attribute] ?? false;
    }

    public function getUser(): ?UserInterface
    {
        return $this->mockUser;
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', $response?->getStatusCode() ?? Response::HTTP_OK);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('admin_dashboard' === $route ? '/admin' : '/', $status);
    }
}
