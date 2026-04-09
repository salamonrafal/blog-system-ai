<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\AvatarImageStorage;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $firstAdministrator = $userRepository->findFirstAdministrator();

        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findForAdminIndex(),
            'first_admin_id' => $firstAdministrator?->getId(),
            'user_stats' => [
                'all' => $userRepository->count([]),
                'active' => $userRepository->countActive(),
                'inactive' => $userRepository->countInactive(),
                'admins' => $userRepository->countAdministrators(),
            ],
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        AvatarImageStorage $avatarImageStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'password_required' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isAdmin = true === $form->get('isAdmin')->getData();
            $plainPassword = $form->get('plainPassword')->getData();

            if (!is_string($plainPassword) || '' === trim($plainPassword)) {
                $this->addFlash('error', $userLanguageResolver->translate('Hasło jest wymagane dla nowego użytkownika.', 'Password is required for a new user.'));

                return $this->redirectToRoute('admin_user_new');
            }

            $user->setRoles($isAdmin ? ['ROLE_ADMIN'] : []);
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $storedAvatar = $avatarImageStorage->store($avatarFile);
                $user->setAvatar($storedAvatar['public_path']);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Użytkownik został utworzony.', 'User created.'));

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $managedUser,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        AvatarImageStorage $avatarImageStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $currentUser = $this->getUser();
        $form = $this->createForm(UserType::class, $managedUser, [
            'is_admin' => in_array('ROLE_ADMIN', $managedUser->getRoles(), true),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isAdmin = true === $form->get('isAdmin')->getData();
            $isCurrentUser = $currentUser instanceof User && $currentUser->getId() === $managedUser->getId();

            if ($isCurrentUser && !$isAdmin) {
                $this->addFlash('error', $userLanguageResolver->translate('Nie możesz odebrać sobie roli administratora.', 'You cannot remove your own administrator role.'));

                return $this->redirectToRoute('admin_user_edit', ['id' => $managedUser->getId()]);
            }

            if ($isCurrentUser && !$managedUser->isActive()) {
                $this->addFlash('error', $userLanguageResolver->translate('Nie możesz dezaktywować aktualnie zalogowanego konta.', 'You cannot deactivate the currently signed-in account.'));

                return $this->redirectToRoute('admin_user_edit', ['id' => $managedUser->getId()]);
            }

            $managedUser->setRoles($isAdmin ? ['ROLE_ADMIN'] : []);

            $plainPassword = $form->get('plainPassword')->getData();
            if (is_string($plainPassword) && '' !== trim($plainPassword)) {
                $managedUser->setPassword($passwordHasher->hashPassword($managedUser, $plainPassword));
            }

            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $storedAvatar = $avatarImageStorage->store($avatarFile, $managedUser->getAvatar());
                $managedUser->setAvatar($storedAvatar['public_path']);
            }

            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Użytkownik został zaktualizowany.', 'User updated.'));

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'managed_user' => $managedUser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        User $managedUser,
        Request $request,
        UserRepository $userRepository,
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager,
        AvatarImageStorage $avatarImageStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_user_'.$managedUser->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $firstAdministrator = $userRepository->findFirstAdministrator();
        if (null !== $firstAdministrator && $firstAdministrator->getId() === $managedUser->getId()) {
            $this->addFlash('error', $userLanguageResolver->translate('Nie możesz usunąć pierwszego administratora.', 'You cannot delete the first administrator.'));

            return $this->redirectToRoute('admin_user_index');
        }

        $avatarPath = $managedUser->getAvatar();

        foreach ($articleRepository->findBy(['createdBy' => $managedUser]) as $article) {
            $article->setCreatedBy(null);
        }

        foreach ($articleRepository->findBy(['updatedBy' => $managedUser]) as $article) {
            $article->setUpdatedBy(null);
        }

        $entityManager->remove($managedUser);
        $entityManager->flush();
        $avatarImageStorage->deleteIfManaged($avatarPath);

        $this->addFlash('success', $userLanguageResolver->translate('Użytkownik został usunięty.', 'User deleted.'));

        return $this->redirectToRoute('admin_user_index');
    }
}
