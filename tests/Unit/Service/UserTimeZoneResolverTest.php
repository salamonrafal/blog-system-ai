<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\UserTimeZoneResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class UserTimeZoneResolverTest extends TestCase
{
    public function testReturnsDefaultTimeZoneWhenCookieIsMissing(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resolver = new UserTimeZoneResolver($requestStack);

        $this->assertSame('UTC', $resolver->getTimeZone());
    }

    public function testReturnsTimeZoneFromCookieWhenItIsValid(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(cookies: ['user_timezone' => 'Europe/Warsaw']));

        $resolver = new UserTimeZoneResolver($requestStack);

        $this->assertSame('Europe/Warsaw', $resolver->getTimeZone());
    }

    public function testFallsBackToDefaultTimeZoneWhenCookieIsInvalid(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(cookies: ['user_timezone' => 'Mars/Olympus']));

        $resolver = new UserTimeZoneResolver($requestStack);

        $this->assertSame('UTC', $resolver->getTimeZone());
    }
}
