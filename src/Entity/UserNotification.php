<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
#[ORM\Table(name: 'user_notification')]
class UserNotification
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(enumType: UserNotificationType::class)]
    private UserNotificationType $type;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $displayedAt = null;

    public function __construct(User $recipient, UserNotificationType $type)
    {
        $this->recipient = $recipient;
        $this->type = $type;
        $this->createdAt = self::utcNow();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function getType(): UserNotificationType
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDisplayedAt(): ?\DateTimeImmutable
    {
        return $this->displayedAt;
    }

    public function isRead(): bool
    {
        return null !== $this->displayedAt;
    }

    public function setDisplayedAt(?\DateTimeImmutable $displayedAt): self
    {
        $this->displayedAt = $displayedAt?->setTimezone(new \DateTimeZone(self::STORAGE_TIMEZONE));

        return $this;
    }

    public function markAsRead(): self
    {
        $this->displayedAt = self::utcNow();

        return $this;
    }

    public function markAsUnread(): self
    {
        $this->displayedAt = null;

        return $this;
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
