<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;

trait AuthenticatedAdminUserTrait
{
    private function resolveAuthenticatedUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
