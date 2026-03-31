<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleCategoryStatus;
use App\Repository\ArticleCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleCategoryRepository::class)]
#[ORM\Table(name: 'article_category')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'Ta nazwa kategorii jest już zajęta.')]
#[UniqueEntity(fields: ['slug'], message: 'Slug kategorii jest już zajęty.')]
class ArticleCategory
{
    use EntityTextNormalizationTrait;

    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Nazwa kategorii jest wymagana.')]
    #[Assert\Length(max: 120, maxMessage: 'Nazwa kategorii może mieć maksymalnie 120 znaków.')]
    #[ORM\Column(length: 120, unique: true)]
    private string $name = '';

    #[Assert\Length(max: 320, maxMessage: 'Krótki opis może mieć maksymalnie 320 znaków.')]
    #[ORM\Column(length: 320, nullable: true)]
    private ?string $shortDescription = null;

    #[Assert\NotBlank(message: 'Slug kategorii jest wymagany.')]
    #[Assert\Length(max: 255, maxMessage: 'Slug kategorii może mieć maksymalnie 255 znaków.')]
    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'json')]
    private array $titles = [];

    #[ORM\Column(type: 'json')]
    private array $descriptions = [];

    #[Assert\Length(max: 255, maxMessage: 'Ikona może mieć maksymalnie 255 znaków.')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(enumType: ArticleCategoryStatus::class)]
    private ArticleCategoryStatus $status = ArticleCategoryStatus::ACTIVE;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $this->normalizeNullableText($shortDescription);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getTitles(): array
    {
        return $this->titles;
    }

    public function setTitles(array $titles): self
    {
        $this->titles = $this->normalizeTranslations($titles);

        return $this;
    }

    public function getTitle(string $language, ?string $fallbackLanguage = 'pl'): ?string
    {
        $language = strtolower(trim($language));
        $fallbackLanguage = null !== $fallbackLanguage ? strtolower(trim($fallbackLanguage)) : null;

        if (isset($this->titles[$language]) && '' !== $this->titles[$language]) {
            return $this->titles[$language];
        }

        if (null !== $fallbackLanguage && isset($this->titles[$fallbackLanguage]) && '' !== $this->titles[$fallbackLanguage]) {
            return $this->titles[$fallbackLanguage];
        }

        return null;
    }

    public function setTitle(string $language, string $title): self
    {
        $language = strtolower(trim($language));
        $title = trim($title);

        if ('' === $language) {
            return $this;
        }

        if ('' === $title) {
            unset($this->titles[$language]);

            return $this;
        }

        $this->titles[$language] = $title;

        return $this;
    }

    public function getDescriptions(): array
    {
        return $this->descriptions;
    }

    public function setDescriptions(array $descriptions): self
    {
        $this->descriptions = $this->normalizeTranslations($descriptions);

        return $this;
    }

    public function getDescription(string $language, ?string $fallbackLanguage = 'pl'): ?string
    {
        $language = strtolower(trim($language));
        $fallbackLanguage = null !== $fallbackLanguage ? strtolower(trim($fallbackLanguage)) : null;

        if (isset($this->descriptions[$language]) && '' !== $this->descriptions[$language]) {
            return $this->descriptions[$language];
        }

        if (null !== $fallbackLanguage && isset($this->descriptions[$fallbackLanguage]) && '' !== $this->descriptions[$fallbackLanguage]) {
            return $this->descriptions[$fallbackLanguage];
        }

        return null;
    }

    public function setDescription(string $language, ?string $description): self
    {
        $language = strtolower(trim($language));
        $description = $this->normalizeNullableText($description);

        if ('' === $language) {
            return $this;
        }

        if (null === $description) {
            unset($this->descriptions[$language]);

            return $this;
        }

        $this->descriptions[$language] = $description;

        return $this;
    }

    public function getLocalizedTitle(string $language, ?string $fallbackLanguage = 'pl'): string
    {
        $title = $this->getTitle($language, $fallbackLanguage);
        if (null !== $title) {
            return $title;
        }

        /** @var mixed $value */
        foreach ($this->titles as $value) {
            if (is_string($value) && '' !== $value) {
                return $value;
            }
        }

        return $this->name;
    }

    public function getLocalizedDescription(string $language, ?string $fallbackLanguage = 'pl'): ?string
    {
        $description = $this->getDescription($language, $fallbackLanguage);
        if (null !== $description) {
            return $description;
        }

        /** @var mixed $value */
        foreach ($this->descriptions as $value) {
            if (is_string($value) && '' !== $value) {
                return $value;
            }
        }

        return null;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $this->normalizeNullableText($icon);

        return $this;
    }

    public function getStatus(): ArticleCategoryStatus
    {
        return $this->status;
    }

    public function setStatus(ArticleCategoryStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return ArticleCategoryStatus::ACTIVE === $this->status;
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
