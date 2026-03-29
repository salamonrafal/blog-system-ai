<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class UserLanguageResolverTest extends TestCase
{
    public function testReturnsDefaultLanguageWhenCookieIsMissing(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resolver = new UserLanguageResolver($requestStack);

        $this->assertSame('pl', $resolver->getLanguage());
    }

    public function testReturnsLanguageFromCookieWhenItIsSupported(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(cookies: ['user_language' => 'en']));

        $resolver = new UserLanguageResolver($requestStack);

        $this->assertSame('en', $resolver->getLanguage());
    }

    public function testFallsBackToDefaultLanguageWhenCookieIsUnsupported(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(cookies: ['user_language' => 'de']));

        $resolver = new UserLanguageResolver($requestStack);

        $this->assertSame('pl', $resolver->getLanguage());
    }

    public function testTranslateReturnsTextForResolvedLanguage(): void
    {
        $polishRequestStack = new RequestStack();
        $polishRequestStack->push(new Request());
        $polishResolver = new UserLanguageResolver($polishRequestStack);

        $englishRequestStack = new RequestStack();
        $englishRequestStack->push(new Request(cookies: ['user_language' => 'en']));
        $englishResolver = new UserLanguageResolver($englishRequestStack);

        $this->assertSame('Wiadomość po polsku.', $polishResolver->translate('Wiadomość po polsku.', 'English message.'));
        $this->assertSame('English message.', $englishResolver->translate('Wiadomość po polsku.', 'English message.'));
    }
}
