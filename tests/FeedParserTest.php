<?php

declare(strict_types=1);

namespace JobsBot\Tests;

use JobsBot\FeedParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FeedParserTest extends TestCase
{
    public function testParsesItemsWithLinks(): void
    {
        $vacancies = (new FeedParser())->parse((string) file_get_contents(__DIR__ . '/fixtures/feed.xml'), 'Test');

        self::assertCount(2, $vacancies);
        self::assertSame('Middle WordPress Developer & WooCommerce', $vacancies[0]->title);
        self::assertSame('Test', $vacancies[0]->source);
        self::assertSame('https://jobs.example.com/vacancies/101/?utm_source=jobsrss', $vacancies[0]->url);
        self::assertSame('Senior PHP <WordPress> Engineer', $vacancies[1]->title);
    }

    public function testIdIgnoresTrackingParametersAndTrailingSlash(): void
    {
        $vacancies = (new FeedParser())->parse((string) file_get_contents(__DIR__ . '/fixtures/feed.xml'), 'Test');

        self::assertSame('jobs.example.com/vacancies/101', $vacancies[0]->id);
        self::assertSame(
            FeedParser::normalizeUrl('https://JOBS.example.com/vacancies/101'),
            $vacancies[0]->id,
        );
    }

    public function testSummaryIsPlainTextWithSeparatedWords(): void
    {
        $vacancies = (new FeedParser())->parse((string) file_get_contents(__DIR__ . '/fixtures/feed.xml'), 'Test');

        self::assertSame('Custom themes ACF PRO REST API', $vacancies[0]->summary);
        self::assertSame('Remote, Europe', $vacancies[1]->summary);
    }

    public function testLongSummaryIsTruncated(): void
    {
        $xml = '<rss><channel><item><title>T</title><link>https://a.test/1</link><description>'
            . str_repeat('word ', 200) . '</description></item></channel></rss>';

        $summary = (new FeedParser())->parse($xml, 'Test')[0]->summary;

        self::assertLessThanOrEqual(281, mb_strlen($summary));
        self::assertStringEndsWith('…', $summary);
    }

    public function testInvalidXmlThrows(): void
    {
        $this->expectException(RuntimeException::class);

        (new FeedParser())->parse('<html>Service unavailable', 'Broken');
    }
}
