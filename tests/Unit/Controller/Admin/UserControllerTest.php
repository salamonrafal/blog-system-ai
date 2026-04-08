<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\UserController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AvatarImageStorage;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class UserControllerTest extends TestCase
{
    public function testIndexBuildsExpectedUserStatistics(): void
    {
        $firstUser = (new User())
            ->setEmail('first@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsActive(true)
            ->setPassword('hashed-password');

        $secondUser = (new User())
            ->setEmail('second@example.com')
            ->setRoles([])
            ->setIsActive(false)
            ->setPassword('hashed-password');

        /** @var UserRepository&MockObject $userRepository */
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([$firstUser, $secondUser]);
        $userRepository
            ->expects($this->once())
            ->method('count')
            ->with([])
            ->willReturn(2);
        $userRepository
            ->expects($this->once())
            ->method('countActive')
            ->willReturn(1);
        $userRepository
            ->expects($this->once())
            ->method('countInactive')
            ->willReturn(1);
        $userRepository
            ->expects($this->once())
            ->method('countAdministrators')
            ->willReturn(1);
        $userRepository
            ->expects($this->once())
            ->method('findFirstAdministrator')
            ->willReturn($firstUser);

        $controller = new TestUserController();
        $response = $controller->index($userRepository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/user/index.html.twig', $controller->capturedView);
        $this->assertCount(2, $controller->capturedParameters['users']);
        $this->assertSame([
            'all' => 2,
            'active' => 1,
            'inactive' => 1,
            'admins' => 1,
        ], $controller->capturedParameters['user_stats']);
        $this->assertSame($firstUser->getId(), $controller->capturedParameters['first_admin_id']);
    }

    public function testNewRendersUserCreationTemplate(): void
    {
        $controller = new TestUserController();
        $response = $controller->new(
            new \Symfony\Component\HttpFoundation\Request(),
            $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
            $this->createMock(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class),
            $this->createMock(AvatarImageStorage::class),
            $this->createMock(UserLanguageResolver::class),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/user/new.html.twig', $controller->capturedView);
    }
}

final class TestUserController extends UserController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory()
            ->create($type, $data, $options);
    }
}
