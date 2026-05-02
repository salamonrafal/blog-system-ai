<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AnalyticsScriptPlacement;
use App\Enum\AnalyticsScriptScope;
use App\Repository\AnalyticsScriptRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AnalyticsScriptRepository::class)]
#[ORM\Table(name: 'analytics_script')]
#[ORM\HasLifecycleCallbacks]
class AnalyticsScript
{
    private const STORAGE_TIMEZONE = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'validation_analytics_script_page_name_required')]
    #[Assert\Length(max: 120, maxMessage: 'validation_analytics_script_page_name_too_long')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9][a-z0-9_-]*$/',
        message: 'validation_analytics_script_page_name_invalid',
    )]
    #[ORM\Column(length: 120, unique: true)]
    private string $pageName = '';

    #[Assert\NotBlank(message: 'validation_analytics_script_name_required')]
    #[Assert\Length(max: 255, maxMessage: 'validation_analytics_script_name_too_long')]
    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(enumType: AnalyticsScriptScope::class)]
    private AnalyticsScriptScope $scope = AnalyticsScriptScope::ALL_PUBLIC;

    #[ORM\Column(enumType: AnalyticsScriptPlacement::class)]
    private AnalyticsScriptPlacement $placement = AnalyticsScriptPlacement::HEAD;

    #[Assert\NotBlank(message: 'validation_analytics_script_snippet_required')]
    #[Assert\Length(max: 10000, maxMessage: 'validation_analytics_script_snippet_too_long')]
    #[ORM\Column(type: 'text')]
    private string $script = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[Assert\GreaterThanOrEqual(value: 0, message: 'validation_analytics_script_position_non_negative')]
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

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

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function setPageName(string $pageName): self
    {
        $this->pageName = strtolower(trim($pageName));

        return $this;
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

    public function getScope(): AnalyticsScriptScope
    {
        return $this->scope;
    }

    public function setScope(AnalyticsScriptScope $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getPlacement(): AnalyticsScriptPlacement
    {
        return $this->placement;
    }

    public function setPlacement(AnalyticsScriptPlacement $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

    public function getScript(): string
    {
        return $this->script;
    }

    public function setScript(string $script): self
    {
        $this->script = trim($script);

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[Assert\Callback]
    public function validateScriptSnippet(ExecutionContextInterface $context): void
    {
        $script = strtolower($this->script);

        if ('' !== $script && !str_contains($script, '<script')) {
            $context
                ->buildViolation('validation_analytics_script_snippet_script_tag_required')
                ->atPath('script')
                ->addViolation();
        }

        foreach (['</head', '</body', '</html'] as $blockedTag) {
            if (str_contains($script, $blockedTag)) {
                $context
                    ->buildViolation('validation_analytics_script_snippet_disallowed_document_tag')
                    ->atPath('script')
                    ->addViolation();

                return;
            }
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = self::utcNow();

        if (!isset($this->createdAt)) {
            $this->createdAt = $this->updatedAt;
        }
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
