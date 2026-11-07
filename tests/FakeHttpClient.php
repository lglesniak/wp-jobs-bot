<?php

declare(strict_types=1);

namespace JobsBot\Tests;

use JobsBot\HttpClient;
use RuntimeException;

final class FakeHttpClient implements HttpClient
{
    /** @var array<int, array{url: string, fields: array<string, string>}> */
    public array $posts = [];

    /**
     * @param array<string, string> $responses URL => body; missing URLs fail like a network error.
     */
    public function __construct(
        private readonly array $responses = [],
        private readonly string $postResponse = '{"ok":true}',
    ) {
    }

    public function get(string $url): string
    {
        return $this->responses[$url] ?? throw new RuntimeException('HTTP request failed with status 500');
    }

    public function post(string $url, array $fields): string
    {
        $this->posts[] = ['url' => $url, 'fields' => $fields];

        return $this->postResponse;
    }
}
