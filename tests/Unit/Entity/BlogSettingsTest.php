<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\BlogSettings;
use PHPUnit\Framework\TestCase;

final class BlogSettingsTest extends TestCase
{
    public function testBlogSettingsExposeAssignedAndNormalizedValues(): void
    {
        $settings = (new BlogSettings())
            ->setBlogTitle('  Moj Blog AI  ')
            ->setHomepageSeoDescription('  Opis strony glownej  ')
            ->setHomepageSocialImage('  /assets/img/blog-share.png  ')
            ->setHomepageSeoKeywords('  php, symfony, ai  ')
            ->setArticlesPerPage(9)
            ->setAdminArticlesPerPage(25);

        $this->assertSame('Moj Blog AI', $settings->getBlogTitle());
        $this->assertSame('Opis strony glownej', $settings->getHomepageSeoDescription());
        $this->assertSame('/assets/img/blog-share.png', $settings->getHomepageSocialImage());
        $this->assertSame('php, symfony, ai', $settings->getHomepageSeoKeywords());
        $this->assertSame(9, $settings->getArticlesPerPage());
        $this->assertSame(25, $settings->getAdminArticlesPerPage());
    }

    public function testBlogSettingsProvideDefaultValues(): void
    {
        $settings = new BlogSettings();

        $this->assertSame(BlogSettings::DEFAULT_BLOG_TITLE, $settings->getBlogTitle());
        $this->assertSame(BlogSettings::DEFAULT_SEO_DESCRIPTION, $settings->getHomepageSeoDescription());
        $this->assertSame(BlogSettings::DEFAULT_SOCIAL_IMAGE, $settings->getHomepageSocialImage());
        $this->assertSame(BlogSettings::DEFAULT_SEO_KEYWORDS, $settings->getHomepageSeoKeywords());
        $this->assertSame(BlogSettings::DEFAULT_ARTICLES_PER_PAGE, $settings->getArticlesPerPage());
        $this->assertSame(BlogSettings::DEFAULT_ADMIN_ARTICLES_PER_PAGE, $settings->getAdminArticlesPerPage());
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
}
