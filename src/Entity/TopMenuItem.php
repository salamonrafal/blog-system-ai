<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TopMenuItemStatus;
use App\Enum\TopMenuItemTargetType;
use App\Repository\TopMenuItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TopMenuItemRepository::class)]
#[ORM\Table(name: 'top_menu_item')]
#[ORM\HasLifecycleCallbacks]
class TopMenuItem
{
    use EntityTextNormalizationTrait;

    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'json')]
    private array $labels = [];

    #[Assert\Length(max: 255, maxMessage: 'validation_top_menu_unique_name_too_long')]
    #[ORM\Column(length: 255, unique: true)]
    private string $uniqueName = '';

    #[ORM\Column(enumType: TopMenuItemTargetType::class)]
    private TopMenuItemTargetType $targetType = TopMenuItemTargetType::BLOG_HOME;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $externalUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $externalUrlOpenInNewWindow = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ArticleCategory $articleCategory = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Article $article = null;

    #[Assert\GreaterThanOrEqual(value: 0, message: 'validation_top_menu_position_non_negative')]
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(enumType: TopMenuItemStatus::class)]
    private TopMenuItemStatus $status = TopMenuItemStatus::ACTIVE;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $children;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $now = self::utcNow();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function setLabels(array $labels): self
    {
        $this->labels = $this->normalizeTranslations($labels);

        return $this;
    }

    public function getUniqueName(): string
    {
        return $this->uniqueName;
    }

    public function setUniqueName(string $uniqueName): self
    {
        $this->uniqueName = trim($uniqueName);

        return $this;
    }

    public function getLabel(string $language, ?string $fallbackLanguage = 'pl'): ?string
    {
        $language = strtolower(trim($language));
        $fallbackLanguage = null !== $fallbackLanguage ? strtolower(trim($fallbackLanguage)) : null;

        if (isset($this->labels[$language]) && '' !== $this->labels[$language]) {
            return $this->labels[$language];
        }

        if (null !== $fallbackLanguage && isset($this->labels[$fallbackLanguage]) && '' !== $this->labels[$fallbackLanguage]) {
            return $this->labels[$fallbackLanguage];
        }

        return null;
    }

    public function setLabel(string $language, string $label): self
    {
        $language = strtolower(trim($language));
        $label = trim($label);

        if ('' === $language) {
            return $this;
        }

        if ('' === $label) {
            unset($this->labels[$language]);

            return $this;
        }

        $this->labels[$language] = $label;

        return $this;
    }

    public function getLocalizedLabel(string $language, ?string $fallbackLanguage = 'pl'): string
    {
        $label = $this->getLabel($language, $fallbackLanguage);
        if (null !== $label) {
            return $label;
        }

        foreach ($this->labels as $value) {
            if (is_string($value) && '' !== $value) {
                return $value;
            }
        }

        return '';
    }

    public function getTargetType(): TopMenuItemTargetType
    {
        return $this->targetType;
    }

    public function setTargetType(TopMenuItemTargetType $targetType): self
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): self
    {
        $this->externalUrl = $this->normalizeNullableText($externalUrl);

        return $this;
    }

    public function isExternalUrlOpenInNewWindow(): bool
    {
        return $this->externalUrlOpenInNewWindow;
    }

    public function setExternalUrlOpenInNewWindow(bool $externalUrlOpenInNewWindow): self
    {
        $this->externalUrlOpenInNewWindow = $externalUrlOpenInNewWindow;

        return $this;
    }

    public function getArticleCategory(): ?ArticleCategory
    {
        return $this->articleCategory;
    }

    public function setArticleCategory(?ArticleCategory $articleCategory): self
    {
        $this->articleCategory = $articleCategory;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getStatus(): TopMenuItemStatus
    {
        return $this->status;
    }

    public function setStatus(TopMenuItemStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return TopMenuItemStatus::ACTIVE === $this->status;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

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

    public function normalizeTargetConfiguration(): self
    {
        match ($this->targetType) {
            TopMenuItemTargetType::NONE, TopMenuItemTargetType::BLOG_HOME => $this
                ->setExternalUrl(null)
                ->setExternalUrlOpenInNewWindow(false)
                ->setArticleCategory(null)
                ->setArticle(null),
            TopMenuItemTargetType::EXTERNAL_URL => $this
                ->setArticleCategory(null)
                ->setArticle(null),
            TopMenuItemTargetType::ARTICLE_CATEGORY => $this
                ->setExternalUrl(null)
                ->setExternalUrlOpenInNewWindow(false)
                ->setArticle(null),
            TopMenuItemTargetType::ARTICLE => $this
                ->setExternalUrl(null)
                ->setExternalUrlOpenInNewWindow(false)
                ->setArticleCategory(null),
        };

        return $this;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        match ($this->targetType) {
            TopMenuItemTargetType::NONE => null,
            TopMenuItemTargetType::EXTERNAL_URL => $this->validateExternalUrl($context),
            TopMenuItemTargetType::ARTICLE_CATEGORY => $this->validateArticleCategory($context),
            TopMenuItemTargetType::ARTICLE => $this->validateArticle($context),
            TopMenuItemTargetType::BLOG_HOME => null,
        };

        $isSelfParent = $this === $this->parent;
        $createsCycle = !$isSelfParent && null !== $this->parent && $this->parent->hasAncestor($this);

        if ($isSelfParent) {
            $context->buildViolation('validation_top_menu_parent_self')
                ->atPath('parent')
                ->addViolation();
        }

        if ($createsCycle) {
            $context->buildViolation('validation_top_menu_parent_cycle')
                ->atPath('parent')
                ->addViolation();
        }

        if (!$isSelfParent && !$createsCycle && null !== $this->parent && null !== $this->parent->getParent()) {
            $context->buildViolation('validation_top_menu_parent_depth')
                ->atPath('parent')
                ->addViolation();
        }
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->ensureUniqueNameIsPresent();
        $now = self::utcNow();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->ensureUniqueNameIsPresent();
        $this->updatedAt = self::utcNow();
    }

    private function ensureUniqueNameIsPresent(): void
    {
        if ('' !== trim($this->uniqueName)) {
            return;
        }

        throw new \LogicException('Top menu item uniqueName cannot be empty when persisting.');
    }

    private function hasAncestor(self $candidate): bool
    {
        $parent = $this->parent;

        while (null !== $parent) {
            if ($parent === $candidate) {
                return true;
            }

            $parent = $parent->getParent();
        }

        return false;
    }

    private function validateExternalUrl(ExecutionContextInterface $context): void
    {
        if (null === $this->externalUrl || '' === $this->externalUrl) {
            $context->buildViolation('validation_top_menu_external_url_required')
                ->atPath('externalUrl')
                ->addViolation();

            return;
        }

        if (mb_strlen($this->externalUrl) > 500) {
            $context->buildViolation('validation_top_menu_external_url_too_long')
                ->atPath('externalUrl')
                ->addViolation();
        }

        if (!preg_match('/^(https?:\/\/).+/', $this->externalUrl)) {
            $context->buildViolation('validation_top_menu_external_url_invalid')
                ->atPath('externalUrl')
                ->addViolation();
        }
    }

    private function validateArticleCategory(ExecutionContextInterface $context): void
    {
        if (null === $this->articleCategory) {
            $context->buildViolation('validation_top_menu_category_required')
                ->atPath('articleCategory')
                ->addViolation();
        }
    }

    private function validateArticle(ExecutionContextInterface $context): void
    {
        if (null === $this->article) {
            $context->buildViolation('validation_top_menu_article_required')
                ->atPath('article')
                ->addViolation();
        }
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
