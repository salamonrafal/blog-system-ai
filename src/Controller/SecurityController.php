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
            'Invalid credentials.' => $userLanguageResolver->translate('Nieprawidłowe dane logowania.', 'Invalid credentials.'),
            'Your account is inactive.' => $userLanguageResolver->translate('Twoje konto jest nieaktywne.', 'Your account is inactive.'),
            'Administrator access is required.' => $userLanguageResolver->translate('To konto nie ma dostępu administratora.', 'This account does not have administrator access.'),
            default => $userLanguageResolver->translate($error->getMessageKey(), $error->getMessageKey()),
        };

        return $this->interpolateAuthenticationMessage($translatedMessage, $error);
    }

    private function interpolateAuthenticationMessage(string $message, AuthenticationException $error): string
    {
        return strtr($message, $error->getMessageData());
    }
}
