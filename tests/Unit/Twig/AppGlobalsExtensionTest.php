<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Service\BlogSettingsProvider;
use App\Service\UserLanguageResolver;
use App\Service\UserNotificationService;
use App\Service\UserTimeZoneResolver;
use App\Twig\AppGlobalsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class AppGlobalsExtensionTest extends TestCase
{
    public function testGetGlobalsExposesAppNameAndBlogSettings(): void
    {
        $settings = (new BlogSettings())
            ->setAppUrl('https://blog.example.com')
            ->setBlogTitle('Blog testowy');

        $provider = $this->createMock(BlogSettingsProvider::class);
        $provider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('en');

        $timeZoneResolver = $this->createMock(UserTimeZoneResolver::class);
        $timeZoneResolver
            ->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('Europe/Warsaw');

        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $notification = new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_SUCCESS);
        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService
            ->expects($this->once())
            ->method('consumeUndisplayedForUserId')
            ->with(7)
            ->willReturn([$notification]);

        $importQueueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $importQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(2);

        $exportQueueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $exportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(3);

        $exportRepository = $this->createMock(ArticleExportRepository::class);
        $exportRepository
            ->expects($this->once())
            ->method('countNew')
            ->willReturn(4);

        $extension = new AppGlobalsExtension(
            $provider,
            $languageResolver,
            $timeZoneResolver,
            $security,
            $userNotificationService,
            $importQueueRepository,
            $exportQueueRepository,
            $exportRepository,
            'test',
        );
        $globals = $extension->getGlobals();

        $this->assertSame('Blog testowy', $globals['app_name']);
        $this->assertSame('https://blog.example.com', $globals['app_url']);
        $this->assertSame($settings, $globals['blog_settings']);
        $this->assertSame('test', $globals['app_env']);
        $this->assertSame('en', $globals['user_language']);
        $this->assertSame('Europe/Warsaw', $globals['user_timezone']);
        $this->assertSame([$notification], $globals['user_notifications']);
        $this->assertSame([
            'queue_status' => 5,
            'imports' => 2,
            'exports' => 4,
            'import_export' => 9,
        ], $globals['admin_shortcut_badges']);
    }

    private function setEntityId(User $user, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($user, 'id');
        $reflectionProperty->setValue($user, $id);
    }
}
