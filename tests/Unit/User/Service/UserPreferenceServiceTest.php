<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Service;

use App\Shared\Service\SettingsServiceInterface;
use App\User\Entity\User;
use App\User\Entity\UserPreference;
use App\User\Repository\UserPreferenceRepositoryInterface;
use App\User\Service\UserPreferenceService;
use App\User\Service\UserPreferenceServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserPreferenceService::class)]
final class UserPreferenceServiceTest extends TestCase
{
    public function testGetReadingModeDefaultsToPage(): void
    {
        $user = new User('demo@localhost', 'hash');
        $service = $this->createService();

        self::assertSame('page', $service->getReadingMode($user));
    }

    public function testSetReadingModePersistsInlineValue(): void
    {
        $user = new User('demo@localhost', 'hash');
        $repository = $this->createMock(UserPreferenceRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findByUserAndKey')
            ->with($user, UserPreferenceServiceInterface::KEY_READING_MODE)
            ->willReturn(null);
        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (UserPreference $preference): bool {
                return $preference->getKey() === UserPreferenceServiceInterface::KEY_READING_MODE
                    && $preference->getValue() === 'inline';
            }), true);

        $service = new UserPreferenceService($repository, $this->createStub(SettingsServiceInterface::class));
        $service->set($user, UserPreferenceServiceInterface::KEY_READING_MODE, 'inline');
    }

    public function testGetSentimentSliderFallsBackToGlobalSetting(): void
    {
        $user = new User('demo@localhost', 'hash');
        $settings = $this->createStub(SettingsServiceInterface::class);
        $settings->method('getSentimentSlider')->willReturn(4);

        $service = $this->createService($settings);

        self::assertSame(4, $service->getSentimentSlider($user));
    }

    public function testIsSentimentNoticeDismissedMatchesStoredValue(): void
    {
        $user = new User('demo@localhost', 'hash');
        $repository = $this->createMock(UserPreferenceRepositoryInterface::class);
        $repository->method('findByUserAndKey')
            ->willReturnMap([
                [$user, UserPreferenceServiceInterface::KEY_SENTIMENT_NOTICE_DISMISSED, new UserPreference($user, UserPreferenceServiceInterface::KEY_SENTIMENT_NOTICE_DISMISSED, '5')],
            ]);

        $service = new UserPreferenceService($repository, $this->createStub(SettingsServiceInterface::class));

        self::assertTrue($service->isSentimentNoticeDismissed($user, 5));
        self::assertFalse($service->isSentimentNoticeDismissed($user, 3));
    }

    private function createService(?SettingsServiceInterface $settings = null): UserPreferenceService
    {
        $repository = $this->createStub(UserPreferenceRepositoryInterface::class);
        $repository->method('findByUserAndKey')->willReturn(null);

        return new UserPreferenceService(
            $repository,
            $settings ?? $this->createStub(SettingsServiceInterface::class),
        );
    }
}
