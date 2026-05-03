<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AnalyticsScript;
use App\Form\AnalyticsScriptType;
use App\Repository\AnalyticsScriptRepository;
use App\Service\UserLanguageResolver;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings/analytics-scripts')]
class AnalyticsScriptController extends AbstractController
{
    #[Route('', name: 'admin_analytics_script_index', methods: ['GET'])]
    public function index(AnalyticsScriptRepository $analyticsScriptRepository): Response
    {
        return $this->render('admin/analytics_script/index.html.twig', [
            'scripts' => $analyticsScriptRepository->findForAdminIndex(),
            'script_stats' => [
                'all' => $analyticsScriptRepository->count([]),
                'enabled' => $analyticsScriptRepository->countEnabled(),
                'disabled' => $analyticsScriptRepository->countDisabled(),
            ],
        ]);
    }

    #[Route('/new', name: 'admin_analytics_script_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AnalyticsScriptRepository $analyticsScriptRepository,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $script = new AnalyticsScript();
        $form = $this->createForm(AnalyticsScriptType::class, $script);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->addDuplicatePageNameError($form, $script, $analyticsScriptRepository, $userLanguageResolver)) {
                return $this->render('admin/analytics_script/new.html.twig', [
                    'form' => $form,
                ]);
            }

            $entityManager->persist($script);
            try {
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->addPageNameAlreadyUsedError($form, $userLanguageResolver);

                return $this->render('admin/analytics_script/new.html.twig', [
                    'form' => $form,
                ]);
            }

            $this->addFlash('success', $userLanguageResolver->translate('Skrypt analityczny został dodany.', 'Analytics script created.'));

            return $this->redirectToRoute('admin_analytics_script_index');
        }

        return $this->render('admin/analytics_script/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_analytics_script_edit', methods: ['GET', 'POST'])]
    public function edit(
        AnalyticsScript $script,
        Request $request,
        EntityManagerInterface $entityManager,
        AnalyticsScriptRepository $analyticsScriptRepository,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(AnalyticsScriptType::class, $script);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->addDuplicatePageNameError($form, $script, $analyticsScriptRepository, $userLanguageResolver)) {
                return $this->render('admin/analytics_script/edit.html.twig', [
                    'script' => $script,
                    'form' => $form,
                ]);
            }

            try {
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->addPageNameAlreadyUsedError($form, $userLanguageResolver);

                return $this->render('admin/analytics_script/edit.html.twig', [
                    'script' => $script,
                    'form' => $form,
                ]);
            }

            $this->addFlash('success', $userLanguageResolver->translate('Skrypt analityczny został zaktualizowany.', 'Analytics script updated.'));

            return $this->redirectToRoute('admin_analytics_script_index');
        }

        return $this->render('admin/analytics_script/edit.html.twig', [
            'script' => $script,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_analytics_script_delete', methods: ['POST'])]
    public function delete(
        AnalyticsScript $script,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_analytics_script_'.$script->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($script);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Skrypt analityczny został usunięty.', 'Analytics script deleted.'));

        return $this->redirectToRoute('admin_analytics_script_index');
    }

    #[Route('/{id}/toggle-enabled', name: 'admin_analytics_script_toggle_enabled', methods: ['POST'])]
    public function toggleEnabled(
        AnalyticsScript $script,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_analytics_script_'.$script->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $script->setEnabled(!$script->isEnabled());
        $entityManager->flush();

        $this->addFlash('success', $script->isEnabled()
            ? $userLanguageResolver->translate('Skrypt analityczny został włączony.', 'Analytics script enabled.')
            : $userLanguageResolver->translate('Skrypt analityczny został wyłączony.', 'Analytics script disabled.')
        );

        return $this->redirectToRoute('admin_analytics_script_index');
    }

    private function addDuplicatePageNameError(
        FormInterface $form,
        AnalyticsScript $script,
        AnalyticsScriptRepository $analyticsScriptRepository,
        UserLanguageResolver $userLanguageResolver,
    ): bool {
        $existingScript = $analyticsScriptRepository->findOneBy(['pageName' => $script->getPageName()]);
        if (!$existingScript instanceof AnalyticsScript || $existingScript->getId() === $script->getId()) {
            return false;
        }

        $this->addPageNameAlreadyUsedError($form, $userLanguageResolver);

        return true;
    }

    private function addPageNameAlreadyUsedError(FormInterface $form, UserLanguageResolver $userLanguageResolver): void
    {
        $form->get('pageName')->addError(new FormError($userLanguageResolver->translate(
            'Ten identyfikator skryptu jest już używany.',
            'This script identifier is already used.',
        )));
    }
}
