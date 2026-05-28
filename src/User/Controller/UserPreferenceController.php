<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Entity\User;
use App\User\Service\UserPreferenceServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UserPreferenceController
{
    public function __construct(
        private ControllerHelper $controller,
        private UserPreferenceServiceInterface $userPreferenceService,
    ) {
    }

    #[Route('/api/user/preferences', name: 'api_user_preferences', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->controller->getUser();
        if (! $user instanceof User) {
            return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $request->headers->get('X-CSRF-Token', '');
        if (! $this->controller->isCsrfTokenValid('user_preference', (string) $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $key = $request->request->getString('key');
        $value = $request->request->getString('value');

        if ($key === '') {
            return new JsonResponse(['error' => 'Preference key is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (! \in_array($key, UserPreferenceServiceInterface::ALLOWED_KEYS, true)) {
            return new JsonResponse(['error' => 'Unsupported preference key.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->userPreferenceService->set($user, $key, $value);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'key' => $key,
            'value' => $this->userPreferenceService->get($user, $key),
        ]);
    }
}
