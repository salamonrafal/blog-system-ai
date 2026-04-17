<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogSettingsRepository::class)]
#[ORM\Table(name: 'blog_settings')]
#[ORM\HasLifecycleCallbacks]
class BlogSettings
{
    public const DEFAULT_APP_URL = 'https://www.salamonrafal.pl';
    public const DEFAULT_BLOG_TITLE = 'Blog System AI';
    public const DEFAULT_SEO_DESCRIPTION = 'Blog o programowaniu, technologii i praktyce tworzenia produktów. Artykuły o PHP, web developmencie, architekturze aplikacji i jakości kodu.';
    public const DEFAULT_SOCIAL_IMAGE = 'https://www.salamonrafal.pl/assets/img/profile.jpg';
    public const DEFAULT_SEO_KEYWORDS = 'blog, programowanie, php, web development, architektura aplikacji, seo, jakość kodu';
    public const DEFAULT_ARTICLES_PER_PAGE = 5;
    public const DEFAULT_ADMIN_LISTING_ITEMS_PER_PAGE = 25;
    public const DEFAULT_RECOMMENDED_ARTICLES_LIMIT = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'validation_blog_settings_app_url_required')]
    #[Assert\Length(max: 255, maxMessage: 'validation_blog_settings_app_url_too_long')]
    #[Assert\Url(
        protocols: ['http', 'https'],
        requireTld: false,
        message: 'validation_blog_settings_app_url_invalid'
    )]
    #[ORM\Column(length: 255)]
    private string $appUrl = self::DEFAULT_APP_URL;

    #[Assert\NotBlank(message: 'validation_blog_settings_blog_title_required')]
    #[Assert\Length(max: 255, maxMessage: 'validation_blog_settings_blog_title_too_long')]
    #[ORM\Column(length: 255)]
    private string $blogTitle = self::DEFAULT_BLOG_TITLE;

    #[Assert\NotBlank(message: 'validation_blog_settings_homepage_seo_description_required')]
    #[Assert\Length(max: 320, maxMessage: 'validation_blog_settings_homepage_seo_description_too_long')]
    #[ORM\Column(length: 320)]
    private string $homepageSeoDescription = self::DEFAULT_SEO_DESCRIPTION;

    #[Assert\NotBlank(message: 'validation_blog_settings_homepage_social_image_required')]
    #[Assert\Length(max: 500, maxMessage: 'validation_blog_settings_homepage_social_image_too_long')]
    #[Assert\Regex(
        pattern: '/^(https?:\/\/|\/).+/',
        message: 'validation_blog_settings_homepage_social_image_invalid'
    )]
    #[ORM\Column(length: 500)]
    private string $homepageSocialImage = self::DEFAULT_SOCIAL_IMAGE;

    #[Assert\NotBlank(message: 'validation_blog_settings_homepage_seo_keywords_required')]
    #[Assert\Length(max: 500, maxMessage: 'validation_blog_settings_homepage_seo_keywords_too_long')]
    #[ORM\Column(length: 500)]
    private string $homepageSeoKeywords = self::DEFAULT_SEO_KEYWORDS;

    #[Assert\NotNull(message: 'validation_blog_settings_articles_per_page_required')]
    #[Assert\Positive(message: 'validation_blog_settings_articles_per_page_positive')]
    #[ORM\Column]
    private int $articlesPerPage = self::DEFAULT_ARTICLES_PER_PAGE;

    #[Assert\NotNull(message: 'validation_blog_settings_admin_listing_items_per_page_required')]
    #[Assert\Positive(message: 'validation_blog_settings_admin_listing_items_per_page_positive')]
    #[ORM\Column(name: 'admin_articles_per_page')]
    private int $adminListingItemsPerPage = self::DEFAULT_ADMIN_LISTING_ITEMS_PER_PAGE;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppUrl(): string
    {
        return $this->appUrl;
    }

    public function setAppUrl(string $appUrl): self
    {
        $this->appUrl = rtrim(trim($appUrl), '/');

        return $this;
    }

    public function getBlogTitle(): string
    {
        return $this->blogTitle;
    }

    public function setBlogTitle(string $blogTitle): self
    {
        $this->blogTitle = trim($blogTitle);

        return $this;
    }

    public function getHomepageSeoDescription(): string
    {
        return $this->homepageSeoDescription;
    }

    public function setHomepageSeoDescription(string $homepageSeoDescription): self
    {
        $this->homepageSeoDescription = trim($homepageSeoDescription);

        return $this;
    }

    public function getHomepageSocialImage(): string
    {
        return $this->homepageSocialImage;
    }

    public function setHomepageSocialImage(string $homepageSocialImage): self
    {
        $this->homepageSocialImage = trim($homepageSocialImage);

        return $this;
    }

    public function getResolvedHomepageSocialImage(): string
    {
        if (str_starts_with($this->homepageSocialImage, 'http://') || str_starts_with($this->homepageSocialImage, 'https://')) {
            return $this->homepageSocialImage;
        }

        return $this->appUrl.$this->homepageSocialImage;
    }

    public function getPreferenceCookieDomain(): ?string
    {
        $host = $this->getAppHost();
        if (null === $host || self::isLocalHost($host) || false !== filter_var($host, \FILTER_VALIDATE_IP)) {
            return null;
        }

        $segments = array_values(array_filter(explode('.', $host), static fn (string $segment): bool => '' !== trim($segment)));
        if ([] === $segments) {
            return null;
        }

        $segmentCount = count($segments);
        if ($segmentCount <= 2) {
            return '.'.implode('.', $segments);
        }

        $topLevelDomain = $segments[$segmentCount - 1];
        $secondLevelDomain = $segments[$segmentCount - 2];
        $usesSecondLevelCountryDomain = 2 === strlen($topLevelDomain) && strlen($secondLevelDomain) <= 3;
        $domainParts = $usesSecondLevelCountryDomain
            ? array_slice($segments, -3)
            : array_slice($segments, -2);

        return '.'.implode('.', $domainParts);
    }

    public function getHomepageSeoKeywords(): string
    {
        return $this->homepageSeoKeywords;
    }

    public function setHomepageSeoKeywords(string $homepageSeoKeywords): self
    {
        $this->homepageSeoKeywords = trim($homepageSeoKeywords);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getArticlesPerPage(): int
    {
        return $this->articlesPerPage;
    }

    public function setArticlesPerPage(int $articlesPerPage): self
    {
        $this->articlesPerPage = $articlesPerPage;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getAdminListingItemsPerPage(): int
    {
        return $this->adminListingItemsPerPage;
    }

    public function setAdminListingItemsPerPage(int $adminListingItemsPerPage): self
    {
        $this->adminListingItemsPerPage = $adminListingItemsPerPage;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Assert\Callback]
    public function validateAppUrlOrigin(ExecutionContextInterface $context): void
    {
        if ('' === $this->appUrl) {
            return;
        }

        $parts = parse_url($this->appUrl);

        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            $context
                ->buildViolation('validation_blog_settings_app_url_origin_invalid')
                ->atPath('appUrl')
                ->addViolation();

            return;
        }

        if (
            isset($parts['path']) ||
            isset($parts['query']) ||
            isset($parts['fragment']) ||
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {
            $context
                ->buildViolation('validation_blog_settings_app_url_origin_only')
                ->atPath('appUrl')
                ->addViolation();
        }
    }

    private function getAppHost(): ?string
    {
        $host = parse_url($this->appUrl, \PHP_URL_HOST);

        if (!is_string($host) || '' === trim($host)) {
            return null;
        }

        $normalizedHost = rtrim(strtolower(trim($host)), '.');
        if ('' === $normalizedHost || !str_contains($normalizedHost, '.')) {
            return null;
        }

        return $normalizedHost;
    }

    private static function isLocalHost(string $host): bool
    {
        $normalizedHost = strtolower(trim($host));

        return str_ends_with($normalizedHost, '.localhost');
    }
}
