<?php

declare(strict_types=1);

namespace JobsBot;

use Throwable;

/**
 * Collects new, relevant vacancies from all feeds.
 */
final class JobChecker
{
    /** @var string[] */
    private array $errors = [];

    /**
     * @param array<int, array{source: string, url: string}> $feeds
     */
    public function __construct(
        private readonly HttpClient $http,
        private readonly FeedParser $parser,
        private readonly VacancyFilter $filter,
        private readonly SeenStore $seen,
        private readonly array $feeds,
        private readonly int $maxPerRun,
    ) {
    }

    /**
     * @return Vacancy[]
     */
    public function findNew(): array
    {
        $this->errors = [];
        $found = [];

        foreach ($this->feeds as $feed) {
            try {
                $vacancies = $this->parser->parse($this->http->get($feed['url']), $feed['source']);
            } catch (Throwable $e) {
                // One broken feed must not stop the others.
                $this->errors[] = "{$feed['source']} ({$feed['url']}): {$e->getMessage()}";
                continue;
            }

            foreach ($vacancies as $vacancy) {
                // The same vacancy can appear in several feeds (e.g. WordPress and WooCommerce searches).
                if (isset($found[$vacancy->id]) || $this->seen->has($vacancy->id) || !$this->filter->accepts($vacancy)) {
                    continue;
                }
                $found[$vacancy->id] = $vacancy;
            }
        }

        return array_slice(array_values($found), 0, $this->maxPerRun);
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
