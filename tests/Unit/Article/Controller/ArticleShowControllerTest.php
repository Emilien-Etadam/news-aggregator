<?php

declare(strict_types=1);

namespace App\Tests\Unit\Article\Controller;

use App\Article\Controller\ArticleShowController;
use App\Article\Entity\Article;
use App\Article\Repository\ArticleRepositoryInterface;
use App\Shared\Entity\Category;
use App\Source\Entity\Source;
use App\User\Entity\User;
use App\User\Repository\UserArticleBookmarkRepositoryInterface;
use App\User\Repository\UserArticleReadRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ArticleShowController::class)]
final class ArticleShowControllerTest extends TestCase
{
    /**
     * @var ControllerHelper&MockObject
     */
    private MockObject $controllerHelper;

    /**
     * @var ArticleRepositoryInterface&MockObject
     */
    private MockObject $articleRepository;

    /**
     * @var UserArticleReadRepositoryInterface&MockObject
     */
    private MockObject $readRepository;

    /**
     * @var UserArticleBookmarkRepositoryInterface&MockObject
     */
    private MockObject $bookmarkRepository;

    private ArticleShowController $controller;

    protected function setUp(): void
    {
        $this->controllerHelper = $this->createMock(ControllerHelper::class);
        $this->articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $this->readRepository = $this->createMock(UserArticleReadRepositoryInterface::class);
        $this->bookmarkRepository = $this->createMock(UserArticleBookmarkRepositoryInterface::class);

        $this->controller = new ArticleShowController(
            $this->controllerHelper,
            $this->articleRepository,
            $this->readRepository,
            $this->bookmarkRepository,
        );
    }

    public function testReturnsUnauthorizedForAnonymousUser(): void
    {
        $this->controllerHelper->method('getUser')->willReturn(null);

        $response = ($this->controller)(1, new Request());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testReturns404WhenArticleMissing(): void
    {
        $this->controllerHelper->method('getUser')->willReturn(new User('test@example.com', 'hash'));
        $this->articleRepository->method('findById')->willReturn(null);

        $response = ($this->controller)(99, new Request());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testRendersReaderPage(): void
    {
        $user = new User('test@example.com', 'hash');
        $article = $this->createArticle();

        $this->controllerHelper->method('getUser')->willReturn($user);
        $this->articleRepository->method('findById')->willReturn($article);
        $this->readRepository->method('findReadArticleIdsForUser')->willReturn([]);
        $this->bookmarkRepository->method('getBookmarkedArticleIds')->willReturn([]);
        $this->controllerHelper->method('generateUrl')->willReturn('/');

        $this->controllerHelper
            ->expects(self::once())
            ->method('render')
            ->with(
                'article/show.html.twig',
                self::callback(static function (array $vars): bool {
                    return isset($vars['article'], $vars['backUrl'])
                        && $vars['isRead'] === false
                        && $vars['isBookmarked'] === false;
                }),
            )
            ->willReturn(new Response('reader page'));

        $response = ($this->controller)(1, new Request());

        self::assertSame('reader page', $response->getContent());
    }

    private function createArticle(): Article
    {
        $category = new Category('Tech', 'tech', 1, '#3b82f6');
        $source = new Source('Test Source', 'https://example.com/feed.xml', $category, new \DateTimeImmutable());
        $article = new Article('Reader title', 'https://example.com/article', $source, new \DateTimeImmutable());
        $article->setContentFullText('Reader body content');

        $reflection = new \ReflectionProperty(Article::class, 'id');
        $reflection->setValue($article, 1);

        return $article;
    }
}
