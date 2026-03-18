<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogSettingsRepository::class)]
#[ORM\Table(name: 'blog_settings')]
#[ORM\HasLifecycleCallbacks]
class BlogSettings
{
    public const DEFAULT_BLOG_TITLE = 'Blog System AI';
    public const DEFAULT_SEO_DESCRIPTION = 'Blog o programowaniu, technologii i praktyce tworzenia produktów. Artykuły o PHP, web developmencie, architekturze aplikacji i jakości kodu.';
    public const DEFAULT_SOCIAL_IMAGE = 'https://www.salamonrafal.pl/assets/img/profile.jpg';
    public const DEFAULT_SEO_KEYWORDS = 'blog, programowanie, php, web development, architektura aplikacji, seo, jakość kodu';
    public const DEFAULT_ARTICLES_PER_PAGE = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Tytuł bloga jest wymagany.')]
    #[Assert\Length(max: 255, maxMessage: 'Tytuł bloga może mieć maksymalnie 255 znaków.')]
    #[ORM\Column(length: 255)]
    private string $blogTitle = self::DEFAULT_BLOG_TITLE;

    #[Assert\NotBlank(message: 'Opis SEO strony głównej jest wymagany.')]
    #[Assert\Length(max: 320, maxMessage: 'Opis SEO strony głównej może mieć maksymalnie 320 znaków.')]
    #[ORM\Column(length: 320)]
    private string $homepageSeoDescription = self::DEFAULT_SEO_DESCRIPTION;

    #[Assert\NotBlank(message: 'Obrazek dla strony głównej jest wymagany.')]
    #[Assert\Length(max: 500, maxMessage: 'Obrazek dla strony głównej może mieć maksymalnie 500 znaków.')]
    #[Assert\Regex(
        pattern: '/^(https?:\/\/|\/).+/',
        message: 'Podaj pełny URL obrazu albo ścieżkę zaczynającą się od /.'
    )]
    #[ORM\Column(length: 500)]
    private string $homepageSocialImage = self::DEFAULT_SOCIAL_IMAGE;

    #[Assert\NotBlank(message: 'Słowa kluczowe SEO są wymagane.')]
    #[Assert\Length(max: 500, maxMessage: 'Słowa kluczowe SEO mogą mieć maksymalnie 500 znaków.')]
    #[ORM\Column(length: 500)]
    private string $homepageSeoKeywords = self::DEFAULT_SEO_KEYWORDS;

    #[Assert\NotNull(message: 'Ilość artykułów na stronę jest wymagana.')]
    #[Assert\Positive(message: 'Ilość artykułów na stronę musi być większa od zera.')]
    #[ORM\Column]
    private int $articlesPerPage = self::DEFAULT_ARTICLES_PER_PAGE;

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
}
