<?php

declare(strict_types=1);

namespace App\Shared\Twig;

use App\Shared\Service\SettingsServiceInterface;
use App\User\Entity\User;
use App\User\Service\UserPreferenceServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class NavigationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SettingsServiceInterface $settingsService,
        private readonly UserPreferenceServiceInterface $userPreferenceService,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $sentimentSlider = $this->userPreferenceService->getSentimentSlider($user);

            return [
                'nav' => [
                    'activeRoute' => $request?->attributes->getString('_route', ''),
                    'searchQuery' => $request?->query->getString('q', ''),
                    'sentimentSlider' => $sentimentSlider,
                    'userPrefs' => [
                        'readingMode' => $this->userPreferenceService->getReadingMode($user),
                        'theme' => $this->userPreferenceService->getTheme($user),
                        'sidebarCollapsed' => $this->userPreferenceService->isSidebarCollapsed($user),
                        'displayLanguage' => $this->userPreferenceService->getDisplayLanguage($user),
                        'sentimentNoticeDismissed' => $this->userPreferenceService->isSentimentNoticeDismissed($user, $sentimentSlider),
                    ],
                ],
            ];
        }

        return [
            'nav' => [
                'activeRoute' => $request?->attributes->getString('_route', ''),
                'searchQuery' => $request?->query->getString('q', ''),
                'sentimentSlider' => $this->settingsService->getSentimentSlider(),
                'userPrefs' => [
                    'readingMode' => 'page',
                    'theme' => 'night',
                    'sidebarCollapsed' => false,
                    'displayLanguage' => 'en',
                    'sentimentNoticeDismissed' => false,
                ],
            ],
        ];
    }
}
