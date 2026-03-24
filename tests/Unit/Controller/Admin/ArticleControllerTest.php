<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleController;
use App\Entity\Article;
use App\Entity\User;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class ArticleControllerTest extends TestCase
{
    public function testAssignToMeSetsCurrentUserAsAuthorWhenArticleHasNoAuthor(): void
    {
        $currentUser = (new User())
            ->setEmail('author@example.com')
            ->setPassword('hashed-password');
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush');
        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->assignToMe($article, $request, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame($currentUser, $article->getCreatedBy());
        $this->assertSame($currentUser, $article->getUpdatedBy());
        $this->assertSame([['success', 'Autor artykułu został przypisany.']], $controller->flashes);
    }

    public function testAssignToMeDoesNotOverwriteExistingAuthor(): void
    {
        $existingAuthor = (new User())
            ->setEmail('existing@example.com')
            ->setPassword('hashed-password');
        $currentUser = (new User())
            ->setEmail('current@example.com')
            ->setPassword('hashed-password');
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article')
            ->setCreatedBy($existingAuthor);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('flush');
        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->assignToMe($article, $request, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame($existingAuthor, $article->getCreatedBy());
        $this->assertSame([['error', 'Artykuł ma już przypisanego autora.']], $controller->flashes);
    }
}

final class TestArticleController extends ArticleController
{
    public ?User $authenticatedUser = null;

    public bool $csrfTokenIsValid = true;

    /** @var list<array{0: string, 1: string}> */
    public array $flashes = [];

    public function getUser(): ?User
    {
        return $this->authenticatedUser;
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenIsValid;
    }

    public function addFlash(string $type, mixed $message): void
    {
        $this->flashes[] = [$type, (string) $message];
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/admin/articles', $status);
    }
}
