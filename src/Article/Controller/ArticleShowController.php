<?php

declare(strict_types=1);

namespace App\Article\Controller;

use App\Article\Entity\Article;
use App\Article\Repository\ArticleRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserArticleBookmarkRepositoryInterface;
use App\User\Repository\UserArticleReadRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArticleShowController
{
    public function __construct(
        private readonly ControllerHelper $controller,
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly UserArticleReadRepositoryInterface $userArticleReadRepository,
        private readonly UserArticleBookmarkRepositoryInterface $userArticleBookmarkRepository,
    ) {
    }

    #[Route('/articles/{id}', name: 'app_article_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): Response
    {
        $user = $this->controller->getUser();
        if (! $user instanceof User) {
            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $article = $this->articleRepository->findById($id);
        if (! $article instanceof Article) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $articleId = (int) $article->getId();
        $readArticleIds = $this->userArticleReadRepository->findReadArticleIdsForUser($user, [$articleId]);
        $bookmarkedArticleIds = $this->userArticleBookmarkRepository->getBookmarkedArticleIds($user, [$articleId]);

        $referer = $request->headers->get('Referer');
        $backUrl = ($referer !== null && str_contains($referer, $request->getSchemeAndHttpHost()))
            ? $referer
            : $this->controller->generateUrl('app_dashboard');

        return $this->controller->render('article/show.html.twig', [
            'article' => $article,
            'isRead' => isset($readArticleIds[$articleId]),
            'isBookmarked' => isset($bookmarkedArticleIds[$articleId]),
            'backUrl' => $backUrl,
        ]);
    }
}
