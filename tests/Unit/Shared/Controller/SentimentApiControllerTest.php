<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Controller;

use App\Shared\Controller\SentimentApiController;
use App\User\Entity\User;
use App\User\Service\UserPreferenceServiceInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversNothing]
final class SentimentApiControllerTest extends TestCase
{
    public function testValidValuePersistsAndReturnsJson(): void
    {
        $user = new User('demo@localhost', 'hash');
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn($user);

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::once())
            ->method('set')
            ->with($user, UserPreferenceServiceInterface::KEY_SENTIMENT_SLIDER, '5');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '5',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('{"value":5}', $response->getContent());
    }

    public function testZeroValueAccepted(): void
    {
        $user = new User('demo@localhost', 'hash');
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn($user);

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::once())
            ->method('set')
            ->with($user, UserPreferenceServiceInterface::KEY_SENTIMENT_SLIDER, '0');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '0',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNegativeValueAccepted(): void
    {
        $user = new User('demo@localhost', 'hash');
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn($user);

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::once())
            ->method('set')
            ->with($user, UserPreferenceServiceInterface::KEY_SENTIMENT_SLIDER, '-7');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '-7',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testValueTooHighReturnsBadRequest(): void
    {
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn(new User('demo@localhost', 'hash'));

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::never())->method('set');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '11',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testValueTooLowReturnsBadRequest(): void
    {
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn(new User('demo@localhost', 'hash'));

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::never())->method('set');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '-11',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testBoundaryMinus10Accepted(): void
    {
        $user = new User('demo@localhost', 'hash');
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn($user);

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::once())->method('set');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '-10',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testBoundaryPlus10Accepted(): void
    {
        $user = new User('demo@localhost', 'hash');
        $helper = $this->createMock(ControllerHelper::class);
        $helper->method('getUser')->willReturn($user);

        $preferences = $this->createMock(UserPreferenceServiceInterface::class);
        $preferences->expects(self::once())->method('set');

        $controller = new SentimentApiController($helper, $preferences);
        $request = Request::create('/api/settings/sentiment', 'POST', [
            'value' => '10',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
