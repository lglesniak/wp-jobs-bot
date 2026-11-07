<?php

declare(strict_types=1);

namespace JobsBot;

use RuntimeException;

final class CurlHttpClient implements HttpClient
{
    private const USER_AGENT = 'wp-jobs-bot/1.0';
    private const TIMEOUT = 20;

    public function get(string $url): string
    {
        return $this->request($url, []);
    }

    public function post(string $url, array $fields): string
    {
        return $this->request($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
        ]);
    }

    /**
     * @param array<int, mixed> $options
     */
    private function request(string $url, array $options): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_USERAGENT => self::USER_AGENT,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Never include the URL in errors: Telegram URLs contain the bot token.
        if ($body === false) {
            throw new RuntimeException("HTTP request failed: {$error}");
        }
        if ($status >= 400) {
            throw new RuntimeException("HTTP request failed with status {$status}");
        }

        return (string) $body;
    }
}
