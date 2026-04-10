<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\UserNotification;
use App\Service\UserNotificationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/notifications')]
class UserNotificationController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    #[Route('/pending', name: 'admin_user_notification_pending', methods: ['POST'])]
    public function pending(Request $request, UserNotificationService $userNotificationService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('consume_user_notifications', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $notifications = $userNotificationService->consumeUndisplayedForUserId(
            $this->resolveAuthenticatedUser()?->getId(),
        );

        return new JsonResponse([
            'notifications' => array_map(
                fn (UserNotification $notification): array => $this->normalizeNotification($notification),
                $notifications,
            ),
        ]);
    }

    #[Route('/recent', name: 'admin_user_notification_recent', methods: ['GET'])]
    public function recent(UserNotificationService $userNotificationService): JsonResponse
    {
        $userId = $this->resolveAuthenticatedUser()?->getId();
        $notifications = $userNotificationService->findLatestForUserId(
            $userId,
            10,
        );

        return new JsonResponse([
            'total_count' => $userNotificationService->countForUserId($userId),
            'notifications' => array_map(
                fn (UserNotification $notification): array => $this->normalizeNotification($notification),
                $notifications,
            ),
        ]);
    }

    #[Route('/{id}/toggle-read', name: 'admin_user_notification_toggle_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleRead(int $id, Request $request, UserNotificationService $userNotificationService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('consume_user_notifications', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $notification = $userNotificationService->toggleReadStatusForUserId(
            $this->resolveAuthenticatedUser()?->getId(),
            $id,
        );

        if (!$notification instanceof UserNotification) {
            return new JsonResponse(['message' => 'Notification not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'notification' => $this->normalizeNotification($notification),
        ]);
    }

    #[Route('/{id}', name: 'admin_user_notification_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request, UserNotificationService $userNotificationService): Response
    {
        if (!$this->isCsrfTokenValid('consume_user_notifications', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $deleted = $userNotificationService->deleteForUserId(
            $this->resolveAuthenticatedUser()?->getId(),
            $id,
        );

        if (!$deleted) {
            return new JsonResponse(['message' => 'Notification not found.'], Response::HTTP_NOT_FOUND);
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'admin_user_notification_delete_all', methods: ['DELETE'])]
    public function deleteAll(Request $request, UserNotificationService $userNotificationService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('consume_user_notifications', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $deletedCount = $userNotificationService->deleteAllForUserId(
            $this->resolveAuthenticatedUser()?->getId(),
        );

        return new JsonResponse([
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     translation_key: string,
     *     action_label_translation_key: string,
     *     action_url: string,
     *     created_at: string,
     *     is_read: bool,
     *     toggle_read_url: string,
     *     delete_url: string,
     * }
     */
    private function normalizeNotification(UserNotification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType()->flashType(),
            'translation_key' => $notification->getType()->translationKey(),
            'action_label_translation_key' => $notification->getType()->actionLabelTranslationKey(),
            'action_url' => $this->urlGenerator->generate(
                $notification->getType()->targetRouteName(),
                [],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            ),
            'created_at' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'is_read' => $notification->isRead(),
            'toggle_read_url' => $this->urlGenerator->generate(
                'admin_user_notification_toggle_read',
                ['id' => $notification->getId()],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            ),
            'delete_url' => $this->urlGenerator->generate(
                'admin_user_notification_delete',
                ['id' => $notification->getId()],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            ),
        ];
    }
}
