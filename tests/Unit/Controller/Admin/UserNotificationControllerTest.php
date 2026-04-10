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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        $this->setNotificationId($notifications[0], 11);
        $this->setNotificationId($notifications[1], 12);

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('consumeUndisplayedForUserId')
            ->with(7)
            ->willReturn($notifications);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->exactly(6))
            ->method('generate')
            ->willReturnMap([
                ['admin_article_import_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/imports'],
                ['admin_article_export_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/exports'],
                ['admin_user_notification_toggle_read', ['id' => 11], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/11/toggle-read'],
                ['admin_user_notification_delete', ['id' => 11], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/11'],
                ['admin_user_notification_toggle_read', ['id' => 12], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/12/toggle-read'],
                ['admin_user_notification_delete', ['id' => 12], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/12'],
            ]);

        $controller = new TestUserNotificationController($user, $urlGenerator);
        $controller->csrfTokenIsValid = true;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'valid');

        $response = $controller->pending($request, $service);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'notifications' => [
                [
                    'id' => 11,
                    'type' => 'success',
                    'translation_key' => 'user_notification_import_completed_success',
                    'action_label_translation_key' => 'user_notification_action_imports',
                    'action_url' => '/admin/imports',
                    'created_at' => $notifications[0]->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'is_read' => false,
                    'toggle_read_url' => '/admin/notifications/11/toggle-read',
                    'delete_url' => '/admin/notifications/11',
                ],
                [
                    'id' => 12,
                    'type' => 'error',
                    'translation_key' => 'user_notification_export_completed_error',
                    'action_label_translation_key' => 'user_notification_action_exports',
                    'action_url' => '/admin/exports',
                    'created_at' => $notifications[1]->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'is_read' => false,
                    'toggle_read_url' => '/admin/notifications/12/toggle-read',
                    'delete_url' => '/admin/notifications/12',
                ],
            ],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRecentReturnsTopTenLatestNotificationsForCurrentUser(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $notifications = [
            new UserNotification($user, UserNotificationType::TOP_MENU_IMPORT_COMPLETED_SUCCESS),
            new UserNotification($user, UserNotificationType::CATEGORY_IMPORT_COMPLETED_ERROR),
        ];
        $this->setNotificationId($notifications[0], 21);
        $this->setNotificationId($notifications[1], 22);

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('findLatestForUserId')
            ->with(7, 10)
            ->willReturn($notifications);
        $service
            ->expects($this->once())
            ->method('countForUserId')
            ->with(7)
            ->willReturn(27);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->exactly(6))
            ->method('generate')
            ->willReturnMap([
                ['admin_top_menu_import_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/top-menu/import'],
                ['admin_category_import_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/category-imports'],
                ['admin_user_notification_toggle_read', ['id' => 21], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/21/toggle-read'],
                ['admin_user_notification_delete', ['id' => 21], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/21'],
                ['admin_user_notification_toggle_read', ['id' => 22], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/22/toggle-read'],
                ['admin_user_notification_delete', ['id' => 22], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/22'],
            ]);

        $controller = new TestUserNotificationController($user, $urlGenerator);

        $response = $controller->recent($service);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'total_count' => 27,
            'notifications' => [
                [
                    'id' => 21,
                    'type' => 'success',
                    'translation_key' => 'user_notification_top_menu_import_completed_success',
                    'action_label_translation_key' => 'user_notification_action_top_menu_imports',
                    'action_url' => '/admin/top-menu/import',
                    'created_at' => $notifications[0]->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'is_read' => false,
                    'toggle_read_url' => '/admin/notifications/21/toggle-read',
                    'delete_url' => '/admin/notifications/21',
                ],
                [
                    'id' => 22,
                    'type' => 'error',
                    'translation_key' => 'user_notification_category_import_completed_error',
                    'action_label_translation_key' => 'user_notification_action_category_imports',
                    'action_url' => '/admin/category-imports',
                    'created_at' => $notifications[1]->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'is_read' => false,
                    'toggle_read_url' => '/admin/notifications/22/toggle-read',
                    'delete_url' => '/admin/notifications/22',
                ],
            ],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRecentThrowsLogicExceptionForNotificationWithoutId(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $notifications = [
            new UserNotification($user, UserNotificationType::TOP_MENU_IMPORT_COMPLETED_SUCCESS),
        ];

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('findLatestForUserId')
            ->with(7, 10)
            ->willReturn($notifications);
        $service
            ->expects($this->once())
            ->method('countForUserId')
            ->with(7)
            ->willReturn(1);

        $controller = new TestUserNotificationController($user, $this->createMock(UrlGeneratorInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot normalize a user notification without an ID.');

        $controller->recent($service);
    }

    public function testToggleReadReturnsUpdatedNotification(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $notification = new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_SUCCESS);
        $this->setNotificationId($notification, 15);
        $notification->markAsRead();

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('toggleReadStatusForUserId')
            ->with(7, 15)
            ->willReturn($notification);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturnMap([
                ['admin_article_import_index', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/imports'],
                ['admin_user_notification_toggle_read', ['id' => 15], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/15/toggle-read'],
                ['admin_user_notification_delete', ['id' => 15], UrlGeneratorInterface::ABSOLUTE_PATH, '/admin/notifications/15'],
            ]);

        $controller = new TestUserNotificationController($user, $urlGenerator);
        $controller->csrfTokenIsValid = true;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'valid');

        $response = $controller->toggleRead(15, $request, $service);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'notification' => [
                'id' => 15,
                'type' => 'success',
                'translation_key' => 'user_notification_import_completed_success',
                'action_label_translation_key' => 'user_notification_action_imports',
                'action_url' => '/admin/imports',
                'created_at' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'is_read' => true,
                'toggle_read_url' => '/admin/notifications/15/toggle-read',
                'delete_url' => '/admin/notifications/15',
            ],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testToggleReadThrowsAccessDeniedWhenCsrfTokenIsInvalid(): void
    {
        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->never())
            ->method('toggleReadStatusForUserId');

        $controller = new TestUserNotificationController(new User(), $this->createMock(UrlGeneratorInterface::class));
        $controller->csrfTokenIsValid = false;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'invalid');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->toggleRead(15, $request, $service);
    }

    public function testToggleReadReturnsNotFoundWhenNotificationDoesNotExist(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('toggleReadStatusForUserId')
            ->with(7, 15)
            ->willReturn(null);

        $controller = new TestUserNotificationController($user, $this->createMock(UrlGeneratorInterface::class));
        $controller->csrfTokenIsValid = true;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'valid');

        $response = $controller->toggleRead(15, $request, $service);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Notification not found.',
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDeleteReturnsNoContentWhenNotificationWasRemoved(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('deleteForUserId')
            ->with(7, 15)
            ->willReturn(true);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $controller = new TestUserNotificationController($user, $urlGenerator);
        $controller->csrfTokenIsValid = true;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'valid');

        $response = $controller->delete(15, $request, $service);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getContent());
    }

    public function testDeleteThrowsAccessDeniedWhenCsrfTokenIsInvalid(): void
    {
        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->never())
            ->method('deleteForUserId');

        $controller = new TestUserNotificationController(new User(), $this->createMock(UrlGeneratorInterface::class));
        $controller->csrfTokenIsValid = false;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'invalid');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->delete(15, $request, $service);
    }

    public function testDeleteReturnsNotFoundWhenNotificationDoesNotExist(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('deleteForUserId')
            ->with(7, 15)
            ->willReturn(false);

        $controller = new TestUserNotificationController($user, $this->createMock(UrlGeneratorInterface::class));
        $controller->csrfTokenIsValid = true;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'valid');

        $response = $controller->delete(15, $request, $service);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Notification not found.',
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDeleteAllReturnsDeletedCount(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');
        $this->setEntityId($user, 7);

        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->once())
            ->method('deleteAllForUserId')
            ->with(7)
            ->willReturn(9);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $controller = new TestUserNotificationController($user, $urlGenerator);
        $controller->csrfTokenIsValid = true;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'valid');

        $response = $controller->deleteAll($request, $service);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'deleted_count' => 9,
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDeleteAllThrowsAccessDeniedWhenCsrfTokenIsInvalid(): void
    {
        $service = $this->createMock(UserNotificationService::class);
        $service
            ->expects($this->never())
            ->method('deleteAllForUserId');

        $controller = new TestUserNotificationController(new User(), $this->createMock(UrlGeneratorInterface::class));
        $controller->csrfTokenIsValid = false;

        $request = new Request();
        $request->headers->set('X-CSRF-Token', 'invalid');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->deleteAll($request, $service);
    }

    private function setEntityId(User $user, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($user, 'id');
        $reflectionProperty->setValue($user, $id);
    }

    private function setNotificationId(UserNotification $notification, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($notification, 'id');
        $reflectionProperty->setValue($notification, $id);
    }
}

final class TestUserNotificationController extends UserNotificationController
{
    public bool $csrfTokenIsValid = true;

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

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenIsValid;
    }
}
