<?php

declare(strict_types=1);

namespace App\Tests\Unit\Article\Service;

use App\Article\Service\ArticleImageExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArticleImageExtractor::class)]
final class ArticleImageExtractorTest extends TestCase
{
    private ArticleImageExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ArticleImageExtractor();
    }

    public function testPrefersFeedImageUrl(): void
    {
        $result = $this->extractor->extract(
            'https://cdn.example.com/feed-thumb.jpg',
            '<img src="https://cdn.example.com/content.jpg" width="200" height="200">',
            'https://example.com/article',
        );

        self::assertSame('https://cdn.example.com/feed-thumb.jpg', $result);
    }

    public function testExtractsFirstImageFromHtml(): void
    {
        $html = '<img src="/images/story.png" width="120" height="80" alt="Story">';

        $result = $this->extractor->extract(null, $html, 'https://example.com/posts/1');

        self::assertSame('https://example.com/images/story.png', $result);
    }

    public function testSkipsTrackingPixels(): void
    {
        $html = '<img src="https://tracker.example/pixel.gif" width="1" height="1">'
            . '<img src="https://cdn.example.com/hero.jpg" width="640" height="360">';

        $result = $this->extractor->extract(null, $html, 'https://example.com/article');

        self::assertSame('https://cdn.example.com/hero.jpg', $result);
    }

    public function testReturnsNullWhenNoImageFound(): void
    {
        self::assertNull($this->extractor->extract(null, '<p>No image here</p>', 'https://example.com/article'));
    }
}
