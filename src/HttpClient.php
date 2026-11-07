<?php

declare(strict_types=1);

namespace JobsBot;

interface HttpClient
{
    public function get(string $url): string;

    /**
     * @param array<string, string> $fields
     */
    public function post(string $url, array $fields): string;
}
