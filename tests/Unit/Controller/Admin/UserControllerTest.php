<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\UserController;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\AvatarImageStorage;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    public function testNewStoresUploadedAvatarAndPersistsUserAvatarPath(): void
    {
        $uploadedAvatar = $this->createUploadedAvatarFile();

        /** @var AvatarImageStorage&MockObject $avatarImageStorage */
        $avatarImageStorage = $this->createMock(AvatarImageStorage::class);
        $avatarImageStorage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(static fn (UploadedFile $file): bool => 'avatar.jpg' === $file->getClientOriginalName()))
            ->willReturn([
                'relative_path' => 'public/uploads/avatars/2026/04/09/avatar-test.jpg',
                'public_path' => '/uploads/avatars/2026/04/09/avatar-test.jpg',
                'original_filename' => 'avatar.jpg',
                'file_size' => 1234,
                'mime_type' => 'image/jpeg',
            ]);

        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface&MockObject $passwordHasher */
        $passwordHasher = $this->createMock(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->isInstanceOf(User::class),
                'secret-password'
            )
            ->willReturn('hashed-password');

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (User $user): bool {
                return 'new@example.com' === $user->getEmail()
                    && '/uploads/avatars/2026/04/09/avatar-test.jpg' === $user->getAvatar()
                    && 'hashed-password' === $user->getPassword()
                    && in_array('ROLE_ADMIN', $user->getRoles(), true);
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->with('Użytkownik został utworzony.', 'User created.')
            ->willReturn('Użytkownik został utworzony.');

        $request = new Request(
            [],
            [
                'user' => [
                    'email' => 'new@example.com',
                    'fullName' => 'Nowy Uzytkownik',
                    'nickname' => 'nowy',
                    'shortBio' => 'Opis',
                    'isAdmin' => '1',
                    'isActive' => '1',
                    'plainPassword' => 'secret-password',
                ],
            ],
            [],
            [],
            [
                'user' => [
                    'avatarFile' => $uploadedAvatar,
                ],
            ],
            [
                'REQUEST_METHOD' => 'POST',
            ],
        );

        $controller = new TestUserController();
        $response = $controller->new(
            $request,
            $entityManager,
            $passwordHasher,
            $avatarImageStorage,
            $userLanguageResolver,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/users', $response->getTargetUrl());
        $this->assertSame([['success', 'Użytkownik został utworzony.']], $controller->flashMessages);
    }

    public function testDeleteRemovesManagedAvatarAfterUserDeletion(): void
    {
        $managedUser = (new User())
            ->setEmail('user@example.com')
            ->setPassword('hashed-password')
            ->setAvatar('/uploads/avatars/2026/04/09/user-avatar.jpg');

        /** @var UserRepository&MockObject $userRepository */
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findFirstAdministrator')
            ->willReturn(null);

        /** @var ArticleRepository&MockObject $articleRepository */
        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturn([]);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($managedUser);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        /** @var AvatarImageStorage&MockObject $avatarImageStorage */
        $avatarImageStorage = $this->createMock(AvatarImageStorage::class);
        $avatarImageStorage
            ->expects($this->once())
            ->method('deleteIfManaged')
            ->with('/uploads/avatars/2026/04/09/user-avatar.jpg');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->with('Użytkownik został usunięty.', 'User deleted.')
            ->willReturn('Użytkownik został usunięty.');

        $controller = new TestUserController();
        $controller->csrfTokens['delete_user_'] = true;

        $response = $controller->delete(
            $managedUser,
            new Request([], ['_token' => 'valid-token']),
            $userRepository,
            $articleRepository,
            $entityManager,
            $avatarImageStorage,
            $userLanguageResolver,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/users', $response->getTargetUrl());
        $this->assertSame([['success', 'Użytkownik został usunięty.']], $controller->flashMessages);
    }

    private function createUploadedAvatarFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar-test-');
        if (false === $path) {
            $this->fail('Failed to create temporary avatar file.');
        }

        file_put_contents($path, base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBUQEBAVFRUVFRUVFRUVFRUVFRUQFRUWFhUVFRUYHSggGBolHRUVITEhJSkrLi4uFx8zODMsNygtLisBCgoKDg0OGhAQGzIlHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAgACAMBIgACEQEDEQH/xAAXAAEBAQEAAAAAAAAAAAAAAAAAAQID/8QAFhEBAQEAAAAAAAAAAAAAAAAAAQAC/9oADAMBAAIQAxAAAAHJrKkP/8QAHBAAAgICAwAAAAAAAAAAAAAAAAECEQMhMRIy/9oACAEBAAEFAvVnMZl5Y0//xAAVEQEBAAAAAAAAAAAAAAAAAAABAP/aAAgBAwEBPwGn/8QAFhEBAQEAAAAAAAAAAAAAAAAAABEB/9oACAECAQE/Aaf/xAAbEAACAQUAAAAAAAAAAAAAAAAAAREhMUFhcf/aAAgBAQAGPwJx2uM2f//EABsQAQADAQEBAQAAAAAAAAAAAAEAESExQVFh/9oACAEBAAE/IXbNk2u0l8Q8Hq6m1F//2gAMAwEAAgADAAAAENAP/8QAFhEBAQEAAAAAAAAAAAAAAAAAARAR/9oACAEDAQE/EEqf/8QAFhEBAQEAAAAAAAAAAAAAAAAAARAR/9oACAECAQE/EEtP/8QAGxABAQADAQEBAAAAAAAAAAAAAREAITFBUWH/2gAIAQEAAT8Qh1p4kMsS2y8FcZbK9mHzonlWQhV8/9k=',
            true,
        ) ?: '');

        return new UploadedFile($path, 'avatar.jpg', 'image/jpeg', null, true);
    }
}

final class TestUserController extends UserController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    /** @var array<string, bool> */
    public array $csrfTokens = [];

    /** @var list<array{0: string, 1: mixed}> */
    public array $flashMessages = [];

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokens[$id] ?? false;
    }

    public function addFlash(string $type, mixed $message): void
    {
        $this->flashMessages[] = [$type, $message];
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('admin_user_index' === $route ? '/admin/users' : '/', $status);
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
