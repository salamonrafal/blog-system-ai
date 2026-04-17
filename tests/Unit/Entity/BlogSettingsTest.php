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
            ->setPreferenceCookieDomainOverride(' Example.com. ')
            ->setHomepageSeoDescription('  Opis strony glownej  ')
            ->setHomepageSocialImage('  /assets/img/blog-share.png  ')
            ->setHomepageSeoKeywords('  php, symfony, ai  ')
            ->setArticlesPerPage(9)
            ->setAdminListingItemsPerPage(25);

        $this->assertSame('https://example.com', $settings->getAppUrl());
        $this->assertSame('Moj Blog AI', $settings->getBlogTitle());
        $this->assertSame('Opis strony glownej', $settings->getHomepageSeoDescription());
        $this->assertSame('/assets/img/blog-share.png', $settings->getHomepageSocialImage());
        $this->assertSame('https://example.com/assets/img/blog-share.png', $settings->getResolvedHomepageSocialImage());
        $this->assertSame('php, symfony, ai', $settings->getHomepageSeoKeywords());
        $this->assertSame(9, $settings->getArticlesPerPage());
        $this->assertSame(25, $settings->getAdminListingItemsPerPage());
        $this->assertSame('.example.com', $settings->getPreferenceCookieDomainOverride());
        $this->assertSame('.example.com', $settings->getPreferenceCookieDomain());
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
        $this->assertSame(BlogSettings::DEFAULT_ADMIN_LISTING_ITEMS_PER_PAGE, $settings->getAdminListingItemsPerPage());
        $this->assertSame(5, BlogSettings::DEFAULT_RECOMMENDED_ARTICLES_LIMIT);
        $this->assertSame('.salamonrafal.pl', $settings->getPreferenceCookieDomain());
    }

    public function testPreferenceCookieDomainReturnsSharedDomainOnlyForSimpleHostsUnlessOverrideIsSet(): void
    {
        $this->assertSame('.example.com', (new BlogSettings())->setAppUrl('https://example.com')->getPreferenceCookieDomain());
        $this->assertNull((new BlogSettings())->setAppUrl('https://blog.example.com')->getPreferenceCookieDomain());
        $this->assertNull((new BlogSettings())->setAppUrl('https://admin.blog.example.co.uk')->getPreferenceCookieDomain());
        $this->assertNull((new BlogSettings())->setAppUrl('http://localhost:8080')->getPreferenceCookieDomain());
        $this->assertNull((new BlogSettings())->setAppUrl('http://localhost.:8080')->getPreferenceCookieDomain());
        $this->assertNull((new BlogSettings())->setAppUrl('http://intranet')->getPreferenceCookieDomain());
        $this->assertNull((new BlogSettings())->setAppUrl('http://127.0.0.1:8080')->getPreferenceCookieDomain());
        $this->assertSame('.example.com', (new BlogSettings())->setAppUrl('https://example.com.')->getPreferenceCookieDomain());
        $this->assertSame('.example.com', (new BlogSettings())->setAppUrl('https://blog.example.com')->setPreferenceCookieDomainOverride('example.com')->getPreferenceCookieDomain());
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
        $invalidCookieDomainViolations = $validator->validate((new BlogSettings())->setPreferenceCookieDomainOverride('.localhost'));
        $invalidCookieDomainWithSemicolonViolations = $validator->validate((new BlogSettings())->setPreferenceCookieDomainOverride('.example.com;secure'));
        $invalidCookieDomainWithUnderscoreViolations = $validator->validate((new BlogSettings())->setPreferenceCookieDomainOverride('.exa_mple.com'));
        $invalidCookieDomainWithHyphenViolations = $validator->validate((new BlogSettings())->setPreferenceCookieDomainOverride('.-example.com'));
        $invalidCookieDomainWithWhitespaceViolations = $validator->validate((new BlogSettings())->setPreferenceCookieDomainOverride('.example .com'));

        $missingHostMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($missingHostViolations)
        );
        $pathMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($pathViolations)
        );
        $emptyTitleMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($emptyTitleViolations)
        );
        $invalidCookieDomainMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($invalidCookieDomainViolations)
        );
        $invalidCookieDomainWithSemicolonMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($invalidCookieDomainWithSemicolonViolations)
        );
        $invalidCookieDomainWithUnderscoreMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($invalidCookieDomainWithUnderscoreViolations)
        );
        $invalidCookieDomainWithHyphenMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($invalidCookieDomainWithHyphenViolations)
        );
        $invalidCookieDomainWithWhitespaceMessages = array_map(
            static fn (mixed $violation): string => $violation->getMessage(),
            iterator_to_array($invalidCookieDomainWithWhitespaceViolations)
        );

        $this->assertContains('validation_blog_settings_app_url_origin_invalid', $missingHostMessages);
        $this->assertContains('validation_blog_settings_app_url_origin_only', $pathMessages);
        $this->assertContains('validation_blog_settings_blog_title_required', $emptyTitleMessages);
        $this->assertContains('validation_blog_settings_preference_cookie_domain_invalid', $invalidCookieDomainMessages);
        $this->assertContains('validation_blog_settings_preference_cookie_domain_invalid', $invalidCookieDomainWithSemicolonMessages);
        $this->assertContains('validation_blog_settings_preference_cookie_domain_invalid', $invalidCookieDomainWithUnderscoreMessages);
        $this->assertContains('validation_blog_settings_preference_cookie_domain_invalid', $invalidCookieDomainWithHyphenMessages);
        $this->assertContains('validation_blog_settings_preference_cookie_domain_invalid', $invalidCookieDomainWithWhitespaceMessages);
    }
}
