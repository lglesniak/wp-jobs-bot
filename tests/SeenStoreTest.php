<?php

declare(strict_types=1);

namespace JobsBot\Tests;

use JobsBot\SeenStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SeenStoreTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/wp-jobs-bot-test-' . bin2hex(random_bytes(4)) . '/seen.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        if (is_dir(dirname($this->path))) {
            rmdir(dirname($this->path));
        }
    }

    public function testMissingFileStartsEmpty(): void
    {
        self::assertFalse((new SeenStore($this->path))->has('a.test/1'));
    }

    public function testSavedIdsAreLoadedAgain(): void
    {
        $store = new SeenStore($this->path);
        $store->add('a.test/1');
        $store->add('a.test/2');
        $store->save();

        $reloaded = new SeenStore($this->path);

        self::assertTrue($reloaded->has('a.test/1'));
        self::assertTrue($reloaded->has('a.test/2'));
        self::assertFalse($reloaded->has('a.test/3'));
    }

    public function testCorruptedFileThrows(): void
    {
        mkdir(dirname($this->path), 0775, true);
        file_put_contents($this->path, '{broken');

        $this->expectException(RuntimeException::class);

        new SeenStore($this->path);
    }
}
