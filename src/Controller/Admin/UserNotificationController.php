<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\UserNotification;
use App\Service\UserNotificationService;
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
                fn (UserNotification $notification): array => [
                    'id' => $notification->getId(),
                    'type' => $notification->getType()->flashType(),
                    'translation_key' => $notification->getType()->translationKey(),
                    'action_label_translation_key' => $notification->getType()->actionLabelTranslationKey(),
                    'action_url' => $this->urlGenerator->generate(
                        $notification->getType()->targetRouteName(),
                        [],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                    ),
                ],
                $notifications,
            ),
        ]);
    }
}
