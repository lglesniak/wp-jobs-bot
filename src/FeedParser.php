<?php

declare(strict_types=1);

namespace JobsBot;

use RuntimeException;

final class FeedParser
{
    private const SUMMARY_LENGTH = 280;

    /**
     * @return Vacancy[]
     */
    public function parse(string $xml, string $source): array
    {
        $previous = libxml_use_internal_errors(true);
        $rss = simplexml_load_string($xml, options: LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($rss === false || !isset($rss->channel)) {
            throw new RuntimeException("Invalid RSS feed from {$source}");
        }

        $vacancies = [];
        foreach ($rss->channel->item as $item) {
            $url = trim((string) $item->link);
            if ($url === '') {
                continue;
            }

            $vacancies[] = new Vacancy(
                id: self::normalizeUrl($url),
                source: $source,
                title: self::cleanText((string) $item->title),
                url: $url,
                summary: self::summarize((string) $item->description),
            );
        }

        return $vacancies;
    }

    /**
     * Tracking parameters (e.g. ?utm_source=jobsrss) differ between feeds,
     * so the URL without query and fragment is used as a stable ID.
     */
    public static function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            return $url;
        }

        $path = rtrim($parts['path'] ?? '', '/');

        return strtolower($parts['host']) . $path;
    }

    private static function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private static function summarize(string $html): string
    {
        // Keep block boundaries as spaces so words from adjacent tags don't stick together.
        $html = (string) preg_replace('/<(br|\/p|\/li|\/h\d)[^>]*>/i', ' ', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = self::cleanText(strip_tags($html));

        if (mb_strlen($text) <= self::SUMMARY_LENGTH) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, self::SUMMARY_LENGTH)) . '…';
    }
}
