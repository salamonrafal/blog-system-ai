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
        $request->setLocale($language);
        $catalogVersion = $translationCatalogLoader->getCatalogVersion(['app', 'validators']);
        $messages = $translationCatalogLoader->loadMergedLanguageMessages(['app', 'validators'], $language);
        $response = new JsonResponse($messages);
        $etag = hash('sha256', $language.':'.$catalogVersion);
        $response->setPublic();
        $response->setEtag($etag);
        $response->headers->set('Content-Language', $language);

        if ($request->query->getString('v') === $catalogVersion) {
            $response->setMaxAge(31536000);
            $response->setSharedMaxAge(31536000);
            $response->setImmutable();
        } else {
            $response->setMaxAge(3600);
            $response->setSharedMaxAge(3600);
        }

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
