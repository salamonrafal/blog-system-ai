<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\ArticleExportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleExportRepository::class)]
#[ORM\Table(name: 'article_export')]
#[ORM\HasLifecycleCallbacks]
class ArticleExport
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: ArticleExportStatus::class)]
    private ArticleExportStatus $status = ArticleExportStatus::NEW;

    #[ORM\Column(enumType: ArticleExportType::class)]
    private ArticleExportType $type = ArticleExportType::ARTICLES;

    #[ORM\Column(length: 500)]
    private string $filePath = '';

    #[ORM\Column]
    private int $articleCount = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $requestedBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = self::utcNow();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ArticleExportStatus
    {
        return $this->status;
    }

    public function setStatus(ArticleExportStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): ArticleExportType
    {
        return $this->type;
    }

    public function setType(ArticleExportType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = trim($filePath);

        return $this;
    }

    public function getArticleCount(): int
    {
        return $this->articleCount;
    }

    public function setArticleCount(int $articleCount): self
    {
        $this->articleCount = max(0, $articleCount);

        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): self
    {
        $this->requestedBy = $requestedBy;

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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = self::utcNow();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = self::utcNow();
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
