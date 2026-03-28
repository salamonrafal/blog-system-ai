<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\UserNotificationController;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Service\UserNotificationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserNotificationControllerTest extends TestCase
{
    public function testPendingReturnsCurrentUserNotifications(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $notifications = [
            new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_SUCCESS),
            new UserNotification($user, UserNotificationType::EXPORT_COMPLETED_ERROR),
        ];

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('consumeUndisplayedForUserId')
            ->with(7)
            ->willReturn($notifications);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                ['admin_article_import_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/imports'],
                ['admin_article_export_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/exports'],
            ]);

        $controller = new TestUserNotificationController($user, $urlGenerator);
        $response = $controller->pending($service);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'notifications' => [
                [
                    'id' => null,
                    'type' => 'success',
                    'translation_key' => 'user_notification_import_completed_success',
                    'action_label_translation_key' => 'user_notification_action_imports',
                    'action_url' => '/admin/imports',
                ],
                [
                    'id' => null,
                    'type' => 'error',
                    'translation_key' => 'user_notification_export_completed_error',
                    'action_label_translation_key' => 'user_notification_action_exports',
                    'action_url' => '/admin/exports',
                ],
            ],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function setEntityId(User $user, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($user, 'id');
        $reflectionProperty->setValue($user, $id);
    }
}

final class TestUserNotificationController extends UserNotificationController
{
    public function __construct(
        private readonly ?User $user,
        UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($urlGenerator);
    }

    protected function getUser(): ?User
    {
        return $this->user;
    }
}
