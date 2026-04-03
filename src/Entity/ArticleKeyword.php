<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleKeywordLanguage;
use App\Repository\ArticleKeywordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleKeywordRepository::class)]
#[ORM\Table(name: 'article_keyword')]
#[ORM\UniqueConstraint(name: 'uniq_article_keyword_language_name', columns: ['language', 'name'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['language', 'name'], message: 'validation_article_keyword_name_unique')]
class ArticleKeyword
{
    use EntityTextNormalizationTrait;

    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'validation_article_keyword_name_required')]
    #[Assert\Length(max: 255, maxMessage: 'validation_article_keyword_name_too_long')]
    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(enumType: ArticleKeywordLanguage::class)]
    private ArticleKeywordLanguage $language = ArticleKeywordLanguage::PL;

    #[ORM\Column(enumType: ArticleCategoryStatus::class)]
    private ArticleCategoryStatus $status = ArticleCategoryStatus::ACTIVE;

    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'validation_article_keyword_color_invalid',
        match: true,
    )]
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'keywords')]
    private Collection $articles;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
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

    public function getLanguage(): ArticleKeywordLanguage
    {
        return $this->language;
    }

    public function setLanguage(ArticleKeywordLanguage $language): self
    {
        $this->language = $language;

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $color = $this->normalizeNullableText($color);
        $this->color = null !== $color ? strtolower($color) : null;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->addKeyword($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            $article->removeKeyword($this);
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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->ensureNameIsPresent();
        $now = self::utcNow();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->ensureNameIsPresent();
        $this->updatedAt = self::utcNow();
    }

    private function ensureNameIsPresent(): void
    {
        if ('' !== trim($this->name)) {
            return;
        }

        throw new \LogicException('Article keyword name cannot be empty when persisting.');
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
