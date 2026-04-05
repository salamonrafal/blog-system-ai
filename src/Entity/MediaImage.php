<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MediaImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaImageRepository::class)]
#[ORM\Table(name: 'media_image')]
#[ORM\HasLifecycleCallbacks]
class MediaImage
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $originalFilename = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customName = null;

    #[ORM\Column(length: 500)]
    private string $filePath = '';

    #[ORM\Column]
    private int $fileSize = 0;

    #[ORM\Column(length: 100)]
    private string $mimeType = '';

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

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(?string $customName): self
    {
        $normalized = is_string($customName) ? trim($customName) : null;
        $this->customName = '' !== $normalized ? $normalized : null;

        return $this;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = trim($filePath);

        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = max(0, $fileSize);

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = trim($mimeType);

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

    public function getStoredFilename(): string
    {
        return basename($this->filePath);
    }

    public function getDisplayName(): string
    {
        if (null !== $this->customName && '' !== $this->customName) {
            return $this->customName;
        }

        $filename = '' !== $this->originalFilename ? $this->originalFilename : $this->getStoredFilename();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (!is_string($extension) || '' === $extension) {
            return $filename;
        }

        return (string) preg_replace('/\.'.preg_quote($extension, '/').'$/', '', $filename);
    }

    public function getOriginalDisplayName(): string
    {
        $filename = '' !== $this->originalFilename ? $this->originalFilename : $this->getStoredFilename();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (!is_string($extension) || '' === $extension) {
            return $filename;
        }

        return (string) preg_replace('/\.'.preg_quote($extension, '/').'$/', '', $filename);
    }

    public function getPublicPath(): string
    {
        return '/'.ltrim((string) preg_replace('#^public/#', '', $this->filePath), '/');
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
