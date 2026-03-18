<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class UserDateExtension extends AbstractExtension
{
    public function __construct(
        private readonly UserTimeZoneResolver $userTimeZoneResolver,
        private readonly UserLanguageResolver $userLanguageResolver,
    )
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('user_date', $this->formatDate(...)),
        ];
    }

    public function formatDate(?\DateTimeInterface $dateTime, string $variant = 'datetime'): string
    {
        if (null === $dateTime) {
            return '';
        }

        $timeZone = new \DateTimeZone($this->userTimeZoneResolver->getTimeZone());
        $localizedDate = \DateTimeImmutable::createFromInterface($dateTime)->setTimezone($timeZone);
        $format = $this->resolveFormat($variant, $this->userLanguageResolver->getLanguage());

        return $localizedDate->format($format);
    }

    private function resolveFormat(string $variant, string $language): string
    {
        return match ($variant) {
            'datetime' => 'pl' === $language ? 'd.m.Y, H:i' : 'M j, Y, h:i A',
            'date' => 'pl' === $language ? 'd.m.Y' : 'M j, Y',
            'stamp' => 'pl' === $language ? 'd.m.Y_H:i' : 'M_j_Y_h:i_A',
            default => $variant,
        };
    }
}
