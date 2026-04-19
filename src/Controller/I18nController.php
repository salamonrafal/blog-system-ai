<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TranslationCatalogLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class I18nController extends AbstractController
{
    #[Route('/i18n/{language}.json', name: 'app_i18n_catalog', methods: ['GET'], requirements: ['language' => 'pl|en'])]
    public function show(string $language, Request $request, TranslationCatalogLoader $translationCatalogLoader): Response
    {
        $messages = $translationCatalogLoader->loadMergedLanguageMessages(['app', 'validators'], $language);
        $response = new JsonResponse($messages);
        $responseContent = $response->getContent() ?: '{}';
        $etag = hash('sha256', $language.':'.$responseContent);
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);
        $response->setEtag($etag);

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
