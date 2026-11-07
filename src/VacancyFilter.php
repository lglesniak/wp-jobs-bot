<?php

declare(strict_types=1);

namespace JobsBot;

final class VacancyFilter
{
    /** @var string[] */
    private array $includeKeywords;

    /** @var string[] */
    private array $excludeKeywords;

    /**
     * @param string[] $includeKeywords At least one must appear in the title or summary; empty list allows all.
     * @param string[] $excludeKeywords None may appear in the title.
     */
    public function __construct(array $includeKeywords, array $excludeKeywords)
    {
        $this->includeKeywords = self::normalize($includeKeywords);
        $this->excludeKeywords = self::normalize($excludeKeywords);
    }

    public function accepts(Vacancy $vacancy): bool
    {
        $title = mb_strtolower($vacancy->title);
        $text = $title . ' ' . mb_strtolower($vacancy->summary);

        if ($this->includeKeywords !== [] && !self::containsAny($text, $this->includeKeywords)) {
            return false;
        }

        return !self::containsAny($title, $this->excludeKeywords);
    }

    /**
     * @param string[] $keywords
     */
    private static function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $keywords
     * @return string[]
     */
    private static function normalize(array $keywords): array
    {
        $keywords = array_map(static fn (string $keyword): string => mb_strtolower(trim($keyword)), $keywords);

        return array_values(array_filter($keywords, static fn (string $keyword): bool => $keyword !== ''));
    }
}
