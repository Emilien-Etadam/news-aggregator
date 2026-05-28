<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use App\User\Entity\User;
use App\User\Service\UserPreferenceServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;

final readonly class SentimentApiController
{
    public function __construct(
        private ControllerHelper $controller,
        private UserPreferenceServiceInterface $userPreferenceService,
    ) {
    }

    #[Route('/api/settings/sentiment', name: 'api_settings_sentiment', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->controller->getUser();
        if (! $user instanceof User) {
            return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $value = $request->request->getInt('value');

        if ($value < -10 || $value > 10) {
            return new JsonResponse([
                'error' => 'Value must be between -10 and +10',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->userPreferenceService->set($user, UserPreferenceServiceInterface::KEY_SENTIMENT_SLIDER, (string) $value);

        return new JsonResponse([
            'value' => $value,
        ]);
    }
}
