<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;

interface UserPreferenceServiceInterface
{
    public const string KEY_READING_MODE = 'reading_mode';

    public const string KEY_THEME = 'theme';

    public const string KEY_SIDEBAR_COLLAPSED = 'sidebar_collapsed';

    public const string KEY_DISPLAY_LANGUAGE = 'display_language';

    public const string KEY_SENTIMENT_SLIDER = 'sentiment_slider';

    public const string KEY_SENTIMENT_NOTICE_DISMISSED = 'sentiment_notice_dismissed';

    /**
     * @var list<string>
     */
    public const array ALLOWED_KEYS = [
        self::KEY_READING_MODE,
        self::KEY_THEME,
        self::KEY_SIDEBAR_COLLAPSED,
        self::KEY_DISPLAY_LANGUAGE,
        self::KEY_SENTIMENT_SLIDER,
        self::KEY_SENTIMENT_NOTICE_DISMISSED,
    ];

    public function get(User $user, string $key, string $default = ''): string;

    public function set(User $user, string $key, string $value): void;

    public function getReadingMode(User $user): string;

    public function getTheme(User $user): string;

    public function isSidebarCollapsed(User $user): bool;

    public function getDisplayLanguage(User $user): string;

    public function getSentimentSlider(User $user): int;

    public function isSentimentNoticeDismissed(User $user, int $currentSliderValue): bool;
}
