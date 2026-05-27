<?php

declare(strict_types=1);

namespace App\Tests\Functional\Article;

use App\Article\Entity\Article;
use App\Shared\Entity\Category;
use App\Shared\Repository\CategoryRepositoryInterface;
use App\Source\Entity\Source;
use App\Source\Repository\SourceRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversNothing]
final class ArticleShowControllerTest extends WebTestCase
{
    public function testShowReturnsReaderPageForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getOrCreateUser());

        $article = $this->createArticle('Reader page test', 'Full text body for the reader page.');

        $client->request('GET', '/articles/' . $article->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Reader page test');
        self::assertSelectorTextContains('.reader-content', 'Full text body for the reader page.');
        self::assertSelectorExists('a[href="' . $article->getUrl() . '"]');
    }

    public function testShowReturns404ForMissingArticle(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getOrCreateUser());

        $client->request('GET', '/articles/999999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowRedirectsAnonymousUserToLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/articles/1');

        self::assertResponseRedirects('/login');
    }

    private function getOrCreateUser(): User
    {
        /** @var UserRepository $repository */
        $repository = self::getContainer()->get(UserRepositoryInterface::class);

        $user = $repository->findFirst();
        if (! $user instanceof User) {
            $user = new User('test@example.com', 'hashed');
            $repository->save($user, flush: true);
        }

        $user->setRoles(['ROLE_ADMIN']);
        $repository->save($user, flush: true);

        return $user;
    }

    private function createArticle(string $title, string $fullText): Article
    {
        $categoryRepository = self::getContainer()->get(CategoryRepositoryInterface::class);
        $categories = $categoryRepository->findAll();
        $category = $categories[0] ?? null;
        if (! $category instanceof Category) {
            $category = new Category('Tech', 'tech-reader', 1, '#3b82f6');
            $categoryRepository->save($category, flush: true);
        }

        $sourceRepository = self::getContainer()->get(SourceRepositoryInterface::class);
        $feedUrl = 'https://reader-test-' . uniqid() . '.example.com/feed.xml';
        $source = new Source('Reader Test Source', $feedUrl, $category, new \DateTimeImmutable());
        $sourceRepository->save($source, flush: true);

        $article = new Article(
            $title,
            'https://reader-test-' . uniqid() . '.example.com/article',
            $source,
            new \DateTimeImmutable(),
        );
        $article->setContentFullText($fullText);

        $articleRepository = self::getContainer()->get(\App\Article\Repository\ArticleRepositoryInterface::class);
        $articleRepository->save($article, flush: true);

        return $article;
    }
}
