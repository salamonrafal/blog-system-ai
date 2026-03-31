<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleImportQueueStatus;
use App\Repository\TopMenuImportQueueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopMenuImportQueueRepository::class)]
#[ORM\Table(name: 'top_menu_import_queue')]
#[ORM\HasLifecycleCallbacks]
class TopMenuImportQueue
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $originalFilename = '';

    #[ORM\Column(length: 500)]
    private string $filePath = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $requestedBy = null;

    #[ORM\Column(enumType: ArticleImportQueueStatus::class)]
    private ArticleImportQueueStatus $status = ArticleImportQueueStatus::PENDING;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $errorMessage = null;

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

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = trim($originalFilename);

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

    public function getStatus(): ArticleImportQueueStatus
    {
        return $this->status;
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

    public function setStatus(ArticleImportQueueStatus $status): self
    {
        $this->status = $status;

        if (ArticleImportQueueStatus::COMPLETED !== $status) {
            $this->processedAt = null;
        }

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $errorMessage = null !== $errorMessage ? trim($errorMessage) : null;
        $this->errorMessage = null === $errorMessage || '' === $errorMessage
            ? null
            : mb_substr($errorMessage, 0, 1000);

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
