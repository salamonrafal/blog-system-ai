<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\BlogSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class BlogSettingsTest extends TestCase
{
    public function testBlogSettingsExposeAssignedAndNormalizedValues(): void
    {
        $settings = (new BlogSettings())
            ->setAppUrl('  https://example.com/  ')
            ->setBlogTitle('  Moj Blog AI  ')
            ->setHomepageSeoDescription('  Opis strony glownej  ')
            ->setHomepageSocialImage('  /assets/img/blog-share.png  ')
            ->setHomepageSeoKeywords('  php, symfony, ai  ')
            ->setArticlesPerPage(9)
            ->setAdminArticlesPerPage(25);

        $this->assertSame('https://example.com', $settings->getAppUrl());
        $this->assertSame('Moj Blog AI', $settings->getBlogTitle());
        $this->assertSame('Opis strony glownej', $settings->getHomepageSeoDescription());
        $this->assertSame('/assets/img/blog-share.png', $settings->getHomepageSocialImage());
        $this->assertSame('https://example.com/assets/img/blog-share.png', $settings->getResolvedHomepageSocialImage());
        $this->assertSame('php, symfony, ai', $settings->getHomepageSeoKeywords());
        $this->assertSame(9, $settings->getArticlesPerPage());
        $this->assertSame(25, $settings->getAdminArticlesPerPage());
    }

    public function testBlogSettingsProvideDefaultValues(): void
    {
        $settings = new BlogSettings();

        $this->assertSame(BlogSettings::DEFAULT_APP_URL, $settings->getAppUrl());
        $this->assertSame(BlogSettings::DEFAULT_BLOG_TITLE, $settings->getBlogTitle());
        $this->assertSame(BlogSettings::DEFAULT_SEO_DESCRIPTION, $settings->getHomepageSeoDescription());
        $this->assertSame(BlogSettings::DEFAULT_SOCIAL_IMAGE, $settings->getHomepageSocialImage());
        $this->assertSame(BlogSettings::DEFAULT_SOCIAL_IMAGE, $settings->getResolvedHomepageSocialImage());
        $this->assertSame(BlogSettings::DEFAULT_SEO_KEYWORDS, $settings->getHomepageSeoKeywords());
        $this->assertSame(BlogSettings::DEFAULT_ARTICLES_PER_PAGE, $settings->getArticlesPerPage());
        $this->assertSame(BlogSettings::DEFAULT_ADMIN_ARTICLES_PER_PAGE, $settings->getAdminArticlesPerPage());
        $this->assertSame(5, BlogSettings::DEFAULT_RECOMMENDED_ARTICLES_LIMIT);
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $settings = new BlogSettings();
        $originalCreatedAt = $settings->getCreatedAt();
        $originalUpdatedAt = $settings->getUpdatedAt();

        usleep(1000);
        $settings->onPrePersist();

        $this->assertGreaterThanOrEqual($originalCreatedAt->getTimestamp(), $settings->getCreatedAt()->getTimestamp());
        $this->assertGreaterThanOrEqual($originalUpdatedAt->getTimestamp(), $settings->getUpdatedAt()->getTimestamp());

        $updatedAtAfterPersist = $settings->getUpdatedAt();

        usleep(1000);
        $settings->onPreUpdate();

        $this->assertGreaterThanOrEqual($updatedAtAfterPersist->getTimestamp(), $settings->getUpdatedAt()->getTimestamp());
    }

    public function testAppUrlValidationAcceptsHttpAndHttpsOriginsOnly(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $validSettings = (new BlogSettings())->setAppUrl('https://example.com/');
        $httpSettings = (new BlogSettings())->setAppUrl('http://localhost:8080');
        $missingHostSettings = (new BlogSettings())->setAppUrl('https:///');
        $pathSettings = (new BlogSettings())->setAppUrl('https://example.com/blog');
        $querySettings = (new BlogSettings())->setAppUrl('https://example.com?ref=1');

        $this->assertSame(0, $validator->validate($validSettings)->count());
        $this->assertSame(0, $validator->validate($httpSettings)->count());
        $this->assertGreaterThan(0, $validator->validate($missingHostSettings)->count());
        $this->assertGreaterThan(0, $validator->validate($pathSettings)->count());
        $this->assertGreaterThan(0, $validator->validate($querySettings)->count());
    }

    public function testBlogSettingsValidationUsesTranslationKeys(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $missingHostViolations = $validator->validate((new BlogSettings())->setAppUrl('https:///'));
        $pathViolations = $validator->validate((new BlogSettings())->setAppUrl('https://example.com/blog'));
        $emptyTitleViolations = $validator->validate((new BlogSettings())->setBlogTitle(''));

        $this->assertSame('validation_blog_settings_app_url_origin_invalid', $missingHostViolations->get(0)->getMessage());
        $this->assertSame('validation_blog_settings_app_url_origin_only', $pathViolations->get(0)->getMessage());
        $this->assertSame('validation_blog_settings_blog_title_required', $emptyTitleViolations->get(0)->getMessage());
    }
}
