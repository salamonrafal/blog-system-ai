<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
#[ORM\HasLifecycleCallbacks]
class Article
{
    public const DEFAULT_HEADLINE_IMAGE = '/assets/img/default-headline-article-pixel-art.png';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'validation_article_title_required')]
    #[Assert\Length(max: 255, maxMessage: 'validation_article_title_too_long')]
    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(enumType: ArticleLanguage::class)]
    private ArticleLanguage $language = ArticleLanguage::PL;

    #[Assert\Length(max: 255, maxMessage: 'validation_article_slug_too_long')]
    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[Assert\Length(max: 320, maxMessage: 'validation_article_excerpt_too_long')]
    #[ORM\Column(length: 320, nullable: true)]
    private ?string $excerpt = null;

    #[Assert\Length(max: 500, maxMessage: 'validation_article_headline_image_too_long')]
    #[Assert\Regex(
        pattern: '/^(https?:\/\/|\/).+/',
        message: 'validation_article_headline_image_invalid'
    )]
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $headlineImage = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $headlineImageEnabled = true;

    #[Assert\NotBlank(message: 'validation_article_content_required')]
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(enumType: ArticleStatus::class)]
    private ArticleStatus $status = ArticleStatus::DRAFT;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLanguage(): ArticleLanguage
    {
        return $this->language;
    }

    public function setLanguage(ArticleLanguage $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getHeadlineImage(): ?string
    {
        return $this->headlineImage;
    }

    public function setHeadlineImage(?string $headlineImage): self
    {
        $headlineImage = null !== $headlineImage ? trim($headlineImage) : null;
        $this->headlineImage = '' === $headlineImage ? null : $headlineImage;

        return $this;
    }

    public function isHeadlineImageEnabled(): bool
    {
        return $this->headlineImageEnabled;
    }

    public function setHeadlineImageEnabled(bool $headlineImageEnabled): self
    {
        $this->headlineImageEnabled = $headlineImageEnabled;

        return $this;
    }

    public function getResolvedHeadlineImage(): ?string
    {
        if (!$this->headlineImageEnabled) {
            return null;
        }

        return $this->headlineImage ?? self::DEFAULT_HEADLINE_IMAGE;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }

    public function setStatus(ArticleStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isPublished(): bool
    {
        return ArticleStatus::PUBLISHED === $this->status;
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
