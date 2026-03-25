<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleExportQueueStatus;
use App\Repository\ArticleExportQueueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleExportQueueRepository::class)]
#[ORM\Table(name: 'article_export_queue')]
#[ORM\HasLifecycleCallbacks]
class ArticleExportQueue
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Article $article;

    #[ORM\Column(enumType: ArticleExportQueueStatus::class)]
    private ArticleExportQueueStatus $status = ArticleExportQueueStatus::PENDING;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Article $article)
    {
        $now = self::utcNow();
        $this->article = $article;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): Article
    {
        return $this->article;
    }

    public function getStatus(): ArticleExportQueueStatus
    {
        return $this->status;
    }

    public function setStatus(ArticleExportQueueStatus $status): self
    {
        $this->status = $status;

        if (ArticleExportQueueStatus::COMPLETED !== $status) {
            $this->processedAt = null;
        }

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = self::normalizeToUtc($processedAt);

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
        $this->processedAt = self::normalizeToUtc($this->processedAt);
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = self::utcNow();
        $this->processedAt = self::normalizeToUtc($this->processedAt);
    }

    private static function normalizeToUtc(?\DateTimeImmutable $dateTime): ?\DateTimeImmutable
    {
        if (null === $dateTime) {
            return null;
        }

        return $dateTime->setTimezone(new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
