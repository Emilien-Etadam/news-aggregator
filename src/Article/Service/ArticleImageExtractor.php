<?php

declare(strict_types=1);

namespace App\Article\Service;

final readonly class ArticleImageExtractor
{
    private const int MIN_DIMENSION = 50;

    private const string TRACKING_PIXEL_PATTERN = '/1x1|pixel|spacer|blank\.(gif|png)|tracking/i';

    public function extract(?string $feedImageUrl, ?string $contentRaw, string $articleUrl): ?string
    {
        if ($feedImageUrl !== null) {
            $resolved = $this->resolveUrl($feedImageUrl, $articleUrl);
            if ($this->isUsableImageUrl($resolved)) {
                return $resolved;
            }
        }

        if ($contentRaw !== null && $contentRaw !== '') {
            return $this->extractFromHtml($contentRaw, $articleUrl);
        }

        return null;
    }

    private function extractFromHtml(string $html, string $baseUrl): ?string
    {
        if (! preg_match_all('/<img\b[^>]*>/i', $html, $matches)) {
            return null;
        }

        foreach ($matches[0] as $tag) {
            $src = $this->extractAttribute($tag, 'src');
            if ($src === null || $src === '') {
                continue;
            }

            if ($this->isTrackingPixel($tag, $src)) {
                continue;
            }

            $resolved = $this->resolveUrl($src, $baseUrl);
            if ($this->isUsableImageUrl($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    private function extractAttribute(string $tag, string $name): ?string
    {
        $pattern = '/\b' . preg_quote($name, '/') . '\s*=\s*(["\'])(.*?)\1/i';
        if (! preg_match($pattern, $tag, $match)) {
            return null;
        }

        return html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function isTrackingPixel(string $tag, string $src): bool
    {
        if (preg_match(self::TRACKING_PIXEL_PATTERN, $src) === 1) {
            return true;
        }

        $width = $this->extractNumericAttribute($tag, 'width');
        $height = $this->extractNumericAttribute($tag, 'height');

        if ($width !== null && $height !== null && ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION)) {
            return true;
        }

        return false;
    }

    private function extractNumericAttribute(string $tag, string $name): ?int
    {
        $value = $this->extractAttribute($tag, $name);
        if ($value === null || ! ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    private function isUsableImageUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true);
    }

    private function resolveUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';

            return $baseScheme . ':' . $url;
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        $parts = parse_url($baseUrl);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        if (str_starts_with($url, '/')) {
            return $origin . $url;
        }

        $basePath = $parts['path'] ?? '/';
        $directory = str_contains($basePath, '/') ? substr($basePath, 0, (int) strrpos($basePath, '/') + 1) : '/';

        return $origin . $directory . $url;
    }
}
