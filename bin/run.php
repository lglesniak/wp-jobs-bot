<?php

declare(strict_types=1);

use JobsBot\CurlHttpClient;
use JobsBot\FeedParser;
use JobsBot\JobChecker;
use JobsBot\SeenStore;
use JobsBot\TelegramNotifier;
use JobsBot\VacancyFilter;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Usage:
 *   php bin/run.php            Send new vacancies to Telegram and remember them.
 *   php bin/run.php --dry-run  Print new vacancies without sending or saving anything.
 */

$config = require __DIR__ . '/../config.php';
$dryRun = in_array('--dry-run', $argv, true);

$http = new CurlHttpClient();
$seen = new SeenStore($config['seen_file']);
$checker = new JobChecker(
    $http,
    new FeedParser(),
    new VacancyFilter($config['include_keywords'], $config['exclude_title_keywords']),
    $seen,
    $config['feeds'],
    $config['max_per_run'],
);

$vacancies = $checker->findNew();

foreach ($checker->errors() as $error) {
    fwrite(STDERR, "Feed error: {$error}\n");
}

if ($dryRun) {
    foreach ($vacancies as $vacancy) {
        echo "[{$vacancy->source}] {$vacancy->title}\n  {$vacancy->url}\n";
    }
    echo count($vacancies) . " new vacancies (dry run, nothing sent)\n";
    exit(0);
}

$token = getenv('TELEGRAM_BOT_TOKEN') ?: '';
$chatId = getenv('TELEGRAM_CHAT_ID') ?: '';
if ($token === '' || $chatId === '') {
    fwrite(STDERR, "TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID environment variables are required.\n");
    exit(1);
}

$notifier = new TelegramNotifier($http, $token, $chatId);
$sent = 0;

try {
    foreach ($vacancies as $vacancy) {
        $notifier->send($vacancy);
        // Mark as seen only after a successful send, so a failed run retries next time.
        $seen->add($vacancy->id);
        $sent++;
    }
} finally {
    $seen->save();
}

echo "Sent {$sent} new vacancies\n";

// Fail the run if every feed is broken, so the problem shows up in GitHub Actions.
exit(count($checker->errors()) === count($config['feeds']) ? 1 : 0);
