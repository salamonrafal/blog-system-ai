<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleExportQueueStatus;
use App\Repository\ArticleKeywordExportQueueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleKeywordExportQueueRepository::class)]
#[ORM\Table(name: 'article_keyword_export_queue')]
#[ORM\HasLifecycleCallbacks]
class ArticleKeywordExportQueue
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $requestedBy = null;

    #[ORM\Column(enumType: ArticleExportQueueStatus::class)]
    private ArticleExportQueueStatus $status = ArticleExportQueueStatus::PENDING;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

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

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): self
    {
        $this->requestedBy = $requestedBy;

        return $this;
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
