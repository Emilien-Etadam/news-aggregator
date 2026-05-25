<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use App\Shared\Service\SettingsService;
use App\Shared\Service\SettingsServiceInterface;
use App\Shared\ValueObject\AiProvider;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController
{
    /**
     * @var list<string>
     */
    private const array AI_SETTING_KEYS = [
        SettingsService::KEY_AI_PROVIDER,
        SettingsService::KEY_AI_OPENAI_BASE_URL,
        SettingsService::KEY_AI_OPENAI_MODEL,
        SettingsService::KEY_AI_OPENAI_API_KEY,
    ];

    public function __construct(
        private readonly ControllerHelper $controller,
        private readonly SettingsServiceInterface $settingsService,
        private readonly string $openrouterApiKey,
        private readonly string $notifierDsn,
    ) {
    }

    #[Route('/settings', name: 'app_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->controller->render('settings/index.html.twig', [
            'aiConfigured' => $this->settingsService->isAiConfigured($this->openrouterApiKey),
            'openAiApiKeyConfigured' => $this->settingsService->hasOpenAiApiKey(),
            'hasNotifierDsn' => $this->notifierDsn !== '' && $this->notifierDsn !== 'null://null',
            'settings' => $this->settingsService->getAll(),
        ]);
    }

    #[Route('/settings/save', name: 'app_settings_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $token = $request->request->getString('_csrf_token');

        if (! $this->controller->isCsrfTokenValid('settings_save', $token)) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $aiError = $this->saveAiProviderSettings($request);
        if ($aiError !== null) {
            return new Response(
                sprintf('<span class="text-error">%s</span>', htmlspecialchars($aiError, ENT_QUOTES)),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $allSettings = $this->settingsService->getAll();

        foreach (array_keys($allSettings) as $key) {
            if (\in_array($key, self::AI_SETTING_KEYS, true)) {
                continue;
            }

            $value = $request->request->getString($key);

            if ($value !== '') {
                $this->settingsService->set($key, $value);
            }
        }

        if ($request->headers->has('HX-Request')) {
            return new Response(
                '<span class="text-success">Settings saved.</span>',
            );
        }

        return $this->controller->redirectToRoute('app_settings');
    }

    private function saveAiProviderSettings(Request $request): ?string
    {
        $providerValue = $request->request->getString('ai_provider');
        $provider = AiProvider::fromString($providerValue);
        $this->settingsService->set(SettingsService::KEY_AI_PROVIDER, $provider->value);

        if ($provider !== AiProvider::OpenAi) {
            return null;
        }

        $baseUrl = trim($request->request->getString('ai_openai_base_url'));
        $model = trim($request->request->getString('ai_openai_model'));
        $apiKey = $request->request->getString('ai_openai_api_key');

        if ($baseUrl === '') {
            $baseUrl = $this->settingsService->getOpenAiBaseUrl();
        }

        if ($model === '') {
            $model = $this->settingsService->getOpenAiModel();
        }

        if ($baseUrl === '' || $model === '') {
            return 'OpenAI-compatible provider requires Base URL and Model.';
        }

        if ($apiKey === '' && ! $this->settingsService->hasOpenAiApiKey()) {
            return 'OpenAI-compatible provider requires an API key.';
        }

        $this->settingsService->set(SettingsService::KEY_AI_OPENAI_BASE_URL, $baseUrl);
        $this->settingsService->set(SettingsService::KEY_AI_OPENAI_MODEL, $model);

        if ($apiKey !== '') {
            $this->settingsService->set(SettingsService::KEY_AI_OPENAI_API_KEY, $apiKey);
        }

        return null;
    }
}
