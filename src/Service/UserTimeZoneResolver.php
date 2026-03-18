<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class UserTimeZoneResolver
{
    private const COOKIE_NAME = 'user_timezone';
    private const DEFAULT_TIMEZONE = 'UTC';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getTimeZone(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $timeZone = $request?->cookies->get(self::COOKIE_NAME);

        if (!is_string($timeZone) || '' === trim($timeZone)) {
            return self::DEFAULT_TIMEZONE;
        }

        $timeZone = trim($timeZone);

        return in_array($timeZone, \DateTimeZone::listIdentifiers(), true)
            ? $timeZone
            : self::DEFAULT_TIMEZONE;
    }
}
