<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Shared\Service\SettingsServiceInterface;
use App\User\Entity\User;
use App\User\Entity\UserPreference;
use App\User\Repository\UserPreferenceRepositoryInterface;

final readonly class UserPreferenceService implements UserPreferenceServiceInterface
{
    public const string DEFAULT_READING_MODE = 'page';

    public const string DEFAULT_THEME = 'night';

    public const string DEFAULT_DISPLAY_LANGUAGE = 'en';

    /**
     * @var list<string>
     */
    private const array ALLOWED_THEMES = [
        'night',
        'dracula',
        'forest',
        'winter',
        'lemonade',
        'valentine',
    ];

    public function __construct(
        private UserPreferenceRepositoryInterface $userPreferenceRepository,
        private SettingsServiceInterface $settingsService,
    ) {
    }

    public function get(User $user, string $key, string $default = ''): string
    {
        $preference = $this->userPreferenceRepository->findByUserAndKey($user, $key);

        if ($preference instanceof UserPreference) {
            return $preference->getValue();
        }

        return $default;
    }

    public function set(User $user, string $key, string $value): void
    {
        if (! \in_array($key, UserPreferenceServiceInterface::ALLOWED_KEYS, true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported preference key "%s".', $key));
        }

        $normalizedValue = $this->normalizeValue($key, $value);

        $preference = $this->userPreferenceRepository->findByUserAndKey($user, $key);

        if ($preference instanceof UserPreference) {
            $preference->setValue($normalizedValue);
        } else {
            $preference = new UserPreference($user, $key, $normalizedValue);
        }

        $this->userPreferenceRepository->save($preference, true);
    }

    public function getReadingMode(User $user): string
    {
        $value = $this->get($user, self::KEY_READING_MODE, self::DEFAULT_READING_MODE);

        return $value === 'inline' ? 'inline' : self::DEFAULT_READING_MODE;
    }

    public function getTheme(User $user): string
    {
        $value = $this->get($user, self::KEY_THEME, self::DEFAULT_THEME);

        return \in_array($value, self::ALLOWED_THEMES, true) ? $value : self::DEFAULT_THEME;
    }

    public function isSidebarCollapsed(User $user): bool
    {
        return $this->get($user, self::KEY_SIDEBAR_COLLAPSED, 'false') === 'true';
    }

    public function getDisplayLanguage(User $user): string
    {
        $value = trim($this->get($user, self::KEY_DISPLAY_LANGUAGE, self::DEFAULT_DISPLAY_LANGUAGE));

        return $value !== '' ? $value : self::DEFAULT_DISPLAY_LANGUAGE;
    }

    public function getSentimentSlider(User $user): int
    {
        $stored = $this->get($user, self::KEY_SENTIMENT_SLIDER, '');

        if ($stored !== '') {
            return $this->clampSentiment((int) $stored);
        }

        return $this->clampSentiment($this->settingsService->getSentimentSlider());
    }

    public function isSentimentNoticeDismissed(User $user, int $currentSliderValue): bool
    {
        $dismissedAt = $this->get($user, self::KEY_SENTIMENT_NOTICE_DISMISSED, '');

        return $dismissedAt === (string) $currentSliderValue;
    }

    private function normalizeValue(string $key, string $value): string
    {
        return match ($key) {
            self::KEY_READING_MODE => $value === 'inline' ? 'inline' : self::DEFAULT_READING_MODE,
            self::KEY_THEME => \in_array($value, self::ALLOWED_THEMES, true) ? $value : self::DEFAULT_THEME,
            self::KEY_SIDEBAR_COLLAPSED => $value === 'true' ? 'true' : 'false',
            self::KEY_DISPLAY_LANGUAGE => trim($value) !== '' ? trim($value) : self::DEFAULT_DISPLAY_LANGUAGE,
            self::KEY_SENTIMENT_SLIDER => (string) $this->clampSentiment((int) $value),
            self::KEY_SENTIMENT_NOTICE_DISMISSED => (string) $this->clampSentiment((int) $value),
            default => throw new \InvalidArgumentException(sprintf('Unsupported preference key "%s".', $key)),
        };
    }

    private function clampSentiment(int $value): int
    {
        return max(-10, min(10, $value));
    }
}
