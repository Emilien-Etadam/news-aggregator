<?php

declare(strict_types=1);

namespace App\Source\Service;

use Laminas\Feed\Reader\Reader;

final readonly class LaminasFeedParserService implements FeedParserServiceInterface
{
    public function parse(string $feedContent): FeedItemCollection
    {
        $feed = Reader::importString($feedContent);
        $items = [];

        foreach ($feed as $entry) {
            $title = $entry->getTitle();
            $url = $entry->getLink();

            /** @phpstan-ignore identical.alwaysFalse, voku.Identical */
            $titleEmpty = $title === null || $title === '';
            /** @phpstan-ignore identical.alwaysFalse, voku.Identical */
            $urlEmpty = $url === null || $url === '';
            if ($titleEmpty) {
                continue;
            }
            if ($urlEmpty) {
                continue;
            }

            $contentRaw = $entry->getContent();
            if ($contentRaw === null || $contentRaw === '') {
                $description = $entry->getDescription();
                $contentRaw = $description ?? '';
            }

            $contentText = $contentRaw !== '' ? $this->stripHtml($contentRaw) : null;
            $contentRawOrNull = $contentRaw !== '' ? $contentRaw : null;

            $publishedAt = null;
            $dateModified = $entry->getDateModified();
            if ($dateModified instanceof \DateTimeInterface) {
                $publishedAt = \DateTimeImmutable::createFromInterface($dateModified);
            }

            $imageUrl = $this->extractFeedImageUrl($entry);

            $items[] = new FeedItem(
                title: $title,
                url: $url,
                contentRaw: $contentRawOrNull,
                contentText: $contentText,
                publishedAt: $publishedAt,
                imageUrl: $imageUrl,
            );
        }

        return new FeedItemCollection($items);
    }

    /**
     * @param \Laminas\Feed\Reader\Entry\EntryInterface $entry
     */
    private function extractFeedImageUrl(object $entry): ?string
    {
        $enclosure = $entry->getEnclosure();
        if ($enclosure !== null && isset($enclosure->url, $enclosure->type) && str_starts_with((string) $enclosure->type, 'image/')) {
            return (string) $enclosure->url;
        }

        return null;
    }

    private function stripHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
