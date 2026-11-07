<?php

declare(strict_types=1);

namespace JobsBot;

use RuntimeException;

final class TelegramNotifier
{
    // Telegram allows about one message per second to the same chat.
    private const SEND_DELAY_MICROSECONDS = 1_100_000;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $token,
        private readonly string $chatId,
        private readonly int $delayMicroseconds = self::SEND_DELAY_MICROSECONDS,
    ) {
    }

    public function send(Vacancy $vacancy): void
    {
        $response = $this->http->post(
            "https://api.telegram.org/bot{$this->token}/sendMessage",
            [
                'chat_id' => $this->chatId,
                'text' => self::formatMessage($vacancy),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => 'true',
            ],
        );

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['ok'] ?? false) !== true) {
            $description = is_array($data) ? (string) ($data['description'] ?? 'unknown error') : 'invalid response';
            throw new RuntimeException("Telegram API error: {$description}");
        }

        usleep($this->delayMicroseconds);
    }

    public static function formatMessage(Vacancy $vacancy): string
    {
        $escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = [
            '<b>' . $escape($vacancy->title) . '</b>',
            '📌 ' . $escape($vacancy->source),
        ];
        if ($vacancy->summary !== '') {
            $lines[] = '';
            $lines[] = $escape($vacancy->summary);
        }
        $lines[] = '';
        $lines[] = '<a href="' . $escape($vacancy->url) . '">Open vacancy</a>';

        return implode("\n", $lines);
    }
}
