<?php

declare(strict_types=1);

namespace JobsBot\Tests;

use JobsBot\TelegramNotifier;
use JobsBot\Vacancy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TelegramNotifierTest extends TestCase
{
    private static function vacancy(): Vacancy
    {
        return new Vacancy('id', 'DOU', 'PHP <WordPress> & WooCommerce', 'https://jobs.test/1?a=1&b=2', 'Remote, B2');
    }

    public function testMessageEscapesHtml(): void
    {
        $message = TelegramNotifier::formatMessage(self::vacancy());

        self::assertStringContainsString('<b>PHP &lt;WordPress&gt; &amp; WooCommerce</b>', $message);
        self::assertStringContainsString('<a href="https://jobs.test/1?a=1&amp;b=2">', $message);
        self::assertStringContainsString('Remote, B2', $message);
    }

    public function testSendPostsToBotApi(): void
    {
        $http = new FakeHttpClient();

        (new TelegramNotifier($http, 'TOKEN', '42', delayMicroseconds: 0))->send(self::vacancy());

        self::assertCount(1, $http->posts);
        self::assertSame('https://api.telegram.org/botTOKEN/sendMessage', $http->posts[0]['url']);
        self::assertSame('42', $http->posts[0]['fields']['chat_id']);
        self::assertSame('HTML', $http->posts[0]['fields']['parse_mode']);
    }

    public function testApiErrorThrows(): void
    {
        $http = new FakeHttpClient(postResponse: '{"ok":false,"description":"Bad Request: chat not found"}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chat not found');

        (new TelegramNotifier($http, 'TOKEN', '42', delayMicroseconds: 0))->send(self::vacancy());
    }
}
