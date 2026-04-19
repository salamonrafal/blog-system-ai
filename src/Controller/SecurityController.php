<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserLanguageResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, UserLanguageResolver $userLanguageResolver): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if (null !== $this->getUser()) {
            return $this->redirectToRoute('blog_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $error,
            'error_i18n_key' => $this->authenticationErrorI18nKey($error),
            'error_i18n_params' => $this->authenticationErrorI18nParams($error),
            'error_message' => $this->translateAuthenticationError($error, $userLanguageResolver),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Logout is handled by Symfony security.');
    }

    #[Route('/access-denied', name: 'app_access_denied', methods: ['GET'])]
    public function accessDenied(): Response
    {
        return $this->render('security/access_denied.html.twig', [], new Response('', Response::HTTP_FORBIDDEN));
    }

    private function translateAuthenticationError(?AuthenticationException $error, UserLanguageResolver $userLanguageResolver): ?string
    {
        if (!$error instanceof AuthenticationException) {
            return null;
        }

        $translatedMessage = match ($error->getMessageKey()) {
            'Invalid credentials.' => $userLanguageResolver->translate('login_error_invalid_credentials'),
            'Your account is inactive.' => $userLanguageResolver->translate('login_error_account_inactive'),
            'Administrator access is required.' => $userLanguageResolver->translate('login_error_administrator_access_required'),
            default => $error->getMessageKey(),
        };

        return $this->interpolateAuthenticationMessage($translatedMessage, $error);
    }

    private function interpolateAuthenticationMessage(string $message, AuthenticationException $error): string
    {
        return strtr($message, $error->getMessageData());
    }

    private function authenticationErrorI18nKey(?AuthenticationException $error): ?string
    {
        if (!$error instanceof AuthenticationException) {
            return null;
        }

        return match ($error->getMessageKey()) {
            'Invalid credentials.' => 'login_error_invalid_credentials',
            'Your account is inactive.' => 'login_error_account_inactive',
            'Administrator access is required.' => 'login_error_administrator_access_required',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function authenticationErrorI18nParams(?AuthenticationException $error): array
    {
        if (!$error instanceof AuthenticationException) {
            return [];
        }

        $parameters = [];

        foreach ($error->getMessageData() as $name => $value) {
            $normalizedName = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string) $name, "{}% \t\n\r\0\x0B"));
            if (!is_string($normalizedName) || '' === $normalizedName) {
                continue;
            }

            $parameters[$normalizedName] = (string) $value;
        }

        return $parameters;
    }
}
