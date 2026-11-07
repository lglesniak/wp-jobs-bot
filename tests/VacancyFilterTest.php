<?php

declare(strict_types=1);

namespace JobsBot\Tests;

use JobsBot\Vacancy;
use JobsBot\VacancyFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VacancyFilterTest extends TestCase
{
    private static function vacancy(string $title, string $summary = ''): Vacancy
    {
        return new Vacancy('id', 'Test', $title, 'https://a.test', $summary);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function titles(): array
    {
        return [
            'developer' => ['Middle WordPress Developer', true],
            'woocommerce' => ['WordPress / WooCommerce Developer (Contract)', true],
            'designer' => ['Web Designer (WordPress)', false],
            'case insensitive' => ['WordPress Developer with SEO experience', false],
            'cyrillic' => ['HTML/CSS/WordPress/Верстальник', false],
            'junior' => ['Junior WordPress developer', false],
        ];
    }

    #[DataProvider('titles')]
    public function testExcludesByTitleKeywords(string $title, bool $expected): void
    {
        $filter = new VacancyFilter([], ['designer', 'SEO', 'верстальник', 'junior']);

        self::assertSame($expected, $filter->accepts(self::vacancy($title)));
    }

    public function testRequiresIncludeKeywordInTitleOrSummary(): void
    {
        $filter = new VacancyFilter(['wordpress', 'woocommerce'], []);

        self::assertTrue($filter->accepts(self::vacancy('PHP Developer', 'Custom WooCommerce plugins')));
        self::assertTrue($filter->accepts(self::vacancy('WordPress Engineer')));
        self::assertFalse($filter->accepts(self::vacancy('Senior DevOps Engineer with AWS', 'Kubernetes')));
    }

    public function testExcludeWinsOverInclude(): void
    {
        $filter = new VacancyFilter(['wordpress'], ['designer']);

        self::assertFalse($filter->accepts(self::vacancy('WordPress Designer')));
    }

    public function testEmptyKeywordsAreIgnored(): void
    {
        $filter = new VacancyFilter(['', ' '], ['', '  ']);

        self::assertTrue($filter->accepts(self::vacancy('Anything')));
    }
}
