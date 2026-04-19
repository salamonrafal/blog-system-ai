<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\I18nController;
use App\Service\TranslationCatalogLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class I18nControllerTest extends TestCase
{
    public function testShowReturnsJsonCatalogForRequestedLanguage(): void
    {
        $controller = new I18nController();
        $translationCatalogLoader = new TranslationCatalogLoader();

        $response = $controller->show('en', new Request(), $translationCatalogLoader);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('en', $response->headers->get('Content-Language'));
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertSame('3600', $response->headers->getCacheControlDirective('max-age'));
        $this->assertSame('3600', $response->headers->getCacheControlDirective('s-maxage'));
        $this->assertSame(
            hash('sha256', 'en:'.$translationCatalogLoader->getCatalogVersion(['app', 'validators'])),
            trim((string) $response->getEtag(), '"'),
        );

        $payload = json_decode($response->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR);

        $this->assertSame('Select an import file.', $payload['validation_import_file_required'] ?? null);
    }

    public function testShowIncludesPolishFallbacksForMissingEnglishKeys(): void
    {
        $controller = new I18nController();

        $response = $controller->show('en', new Request(), new TranslationCatalogLoader());
        $payload = json_decode($response->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR);

        $this->assertSame('{{count}} powiadomienia', $payload['admin_shortcut_notifications_badge_few'] ?? null);
    }

    public function testShowUsesImmutableCachingForVersionedCatalogRequests(): void
    {
        $controller = new I18nController();
        $translationCatalogLoader = new TranslationCatalogLoader();
        $catalogVersion = $translationCatalogLoader->getCatalogVersion(['app', 'validators']);

        $response = $controller->show('en', new Request(['v' => $catalogVersion]), $translationCatalogLoader);

        $this->assertSame('31536000', $response->headers->getCacheControlDirective('max-age'));
        $this->assertSame('31536000', $response->headers->getCacheControlDirective('s-maxage'));
        $this->assertTrue($response->headers->hasCacheControlDirective('immutable'));
    }

    public function testShowRejectsUnsupportedLanguage(): void
    {
        $controller = new I18nController();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unsupported i18n catalog language.');

        $controller->show('de', new Request(), new TranslationCatalogLoader());
    }
}
