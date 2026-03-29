<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlogSettings;
use App\Form\BlogSettingsType;
use App\Repository\BlogSettingsRepository;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings/blog')]
class BlogSettingsController extends AbstractController
{
    #[Route('', name: 'admin_blog_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        BlogSettingsRepository $blogSettingsRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $settings = $blogSettingsRepository->findCurrent() ?? new BlogSettings();

        $form = $this->createForm(BlogSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $settings->getId()) {
                $entityManager->persist($settings);
            }

            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Ustawienia bloga zostały zapisane.', 'Blog settings have been saved.'));

            return $this->redirectToRoute('admin_blog_settings');
        }

        return $this->render('admin/blog_settings/index.html.twig', [
            'form' => $form,
            'settings' => $settings,
        ]);
    }

}
