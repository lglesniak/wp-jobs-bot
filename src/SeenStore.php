<?php

declare(strict_types=1);

namespace JobsBot;

use RuntimeException;

/**
 * Remembers already-sent vacancy IDs in a JSON file, so each vacancy is sent once.
 */
final class SeenStore
{
    // Feeds only show recent items, so older IDs can be dropped to keep the file small.
    private const MAX_IDS = 2000;

    /** @var array<string, true> */
    private array $ids = [];

    public function __construct(private readonly string $path)
    {
        if (!is_file($path)) {
            return;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            throw new RuntimeException("Seen file is not valid JSON: {$path}");
        }

        foreach ($data as $id) {
            if (is_string($id)) {
                $this->ids[$id] = true;
            }
        }
    }

    public function has(string $id): bool
    {
        return isset($this->ids[$id]);
    }

    public function add(string $id): void
    {
        // Re-insert so the most recently seen IDs stay at the end and survive trimming.
        unset($this->ids[$id]);
        $this->ids[$id] = true;
    }

    public function save(): void
    {
        $ids = array_slice(array_keys($this->ids), -self::MAX_IDS);

        $dir = dirname($this->path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create directory: {$dir}");
        }

        $json = json_encode($ids, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($this->path, $json . "\n", LOCK_EX) === false) {
            throw new RuntimeException("Cannot write seen file: {$this->path}");
        }
    }
}
