<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $targetPath = $this->getTargetPath($session, 'main');
            if (is_string($targetPath) && '' !== trim($targetPath)) {
                return new RedirectResponse($targetPath);
            }
        }

        $route = in_array('ROLE_ADMIN', $token->getRoleNames(), true) ? 'admin_dashboard' : 'blog_index';

        return new RedirectResponse($this->router->generate($route));
    }
}
