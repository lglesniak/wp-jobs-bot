<?php

declare(strict_types=1);

namespace JobsBot\Tests;

use JobsBot\FeedParser;
use JobsBot\JobChecker;
use JobsBot\SeenStore;
use JobsBot\VacancyFilter;
use PHPUnit\Framework\TestCase;

final class JobCheckerTest extends TestCase
{
    private const FEED_A = 'https://a.test/feed';
    private const FEED_B = 'https://b.test/feed';

    private static function rss(string ...$items): string
    {
        $xml = '';
        foreach ($items as $i => $title) {
            $xml .= "<item><title>{$title}</title><link>https://jobs.test/{$i}/?utm_source=x</link></item>";
        }

        return "<rss><channel>{$xml}</channel></rss>";
    }

    private static function checker(FakeHttpClient $http, SeenStore $seen, int $maxPerRun = 20): JobChecker
    {
        return new JobChecker(
            $http,
            new FeedParser(),
            new VacancyFilter([], ['designer']),
            $seen,
            [
                ['source' => 'A', 'url' => self::FEED_A],
                ['source' => 'B', 'url' => self::FEED_B],
            ],
            $maxPerRun,
        );
    }

    private static function emptyStore(): SeenStore
    {
        return new SeenStore(sys_get_temp_dir() . '/wp-jobs-bot-missing-' . bin2hex(random_bytes(4)) . '.json');
    }

    public function testReturnsNewRelevantVacanciesOnce(): void
    {
        $http = new FakeHttpClient([
            self::FEED_A => self::rss('WordPress Developer', 'Web Designer'),
            // Same links as feed A: the duplicate must be dropped.
            self::FEED_B => self::rss('WordPress Developer'),
        ]);

        $titles = array_map(static fn ($v) => $v->title, self::checker($http, self::emptyStore())->findNew());

        self::assertSame(['WordPress Developer'], $titles);
    }

    public function testSkipsAlreadySeenVacancies(): void
    {
        $seen = self::emptyStore();
        $seen->add('jobs.test/0');
        $http = new FakeHttpClient([
            self::FEED_A => self::rss('Seen before', 'Brand new'),
            self::FEED_B => self::rss(),
        ]);

        $titles = array_map(static fn ($v) => $v->title, self::checker($http, $seen)->findNew());

        self::assertSame(['Brand new'], $titles);
    }

    public function testBrokenFeedIsReportedAndOthersStillWork(): void
    {
        $http = new FakeHttpClient([self::FEED_B => self::rss('PHP WordPress Engineer')]);
        $checker = self::checker($http, self::emptyStore());

        $vacancies = $checker->findNew();

        self::assertCount(1, $vacancies);
        self::assertCount(1, $checker->errors());
        self::assertStringContainsString('A (' . self::FEED_A . ')', $checker->errors()[0]);
    }

    public function testRespectsMaxPerRun(): void
    {
        $http = new FakeHttpClient([
            self::FEED_A => self::rss('One', 'Two', 'Three'),
            self::FEED_B => self::rss(),
        ]);

        self::assertCount(2, self::checker($http, self::emptyStore(), maxPerRun: 2)->findNew());
    }
}
