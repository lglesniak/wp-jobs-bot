<?php

declare(strict_types=1);

namespace JobsBot;

final class Vacancy
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $title,
        public readonly string $url,
        public readonly string $summary,
    ) {
    }
}
