<?php

declare(strict_types=1);

/**
 * Bot configuration: which feeds to watch and which vacancies to skip.
 */
return [
    'feeds' => [
        ['source' => 'Djinni', 'url' => 'https://djinni.co/jobs/rss/?primary_keyword=WordPress'],
        ['source' => 'DOU', 'url' => 'https://jobs.dou.ua/vacancies/feeds/?search=wordpress'],
        ['source' => 'DOU', 'url' => 'https://jobs.dou.ua/vacancies/feeds/?search=woocommerce'],
        ['source' => 'jobs.wordpress.net', 'url' => 'https://jobs.wordpress.net/feed/'],
    ],

    // A vacancy must mention at least one of these in its title or description.
    // Some feeds ignore search parameters and return every vacancy, so this is the safety net.
    'include_keywords' => ['wordpress', 'woocommerce', 'gutenberg'],

    // Vacancies whose title contains any of these (case-insensitive) are skipped.
    'exclude_title_keywords' => [
        'designer', 'дизайнер',
        // Only pure SEO roles: "WordPress Developer with SEO experience" is still relevant.
        'seo specialist', 'seo-спеціаліст', 'seo manager',
        'верстальник', 'layout',
        'content manager', 'контент',
        'marketing', 'маркетолог',
        'junior', 'trainee', 'intern', 'student', 'стажер',
        'web manager',
    ],

    // Guard against flooding the chat if a feed suddenly returns many new items.
    'max_per_run' => 20,

    'seen_file' => __DIR__ . '/data/seen.json',
];
