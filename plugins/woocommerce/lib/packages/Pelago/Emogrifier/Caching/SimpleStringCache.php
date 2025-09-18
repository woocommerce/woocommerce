<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Pelago\Emogrifier\Caching;

/**
 * This cache caches string values with string keys. It is not PSR-6-compliant.
 *
 * Usage:
 *
 * ```php
 * $cache = new SimpleStringCache();
 * $cache->set($key, $value);
 * …
 * if ($cache->has($key) {
 *     $cachedValue = $cache->get($value);
 * }
 * ```
 *
 * @internal
 */
final class SimpleStringCache
{
    /**
     * @var array<non-empty-string, string>
     */
    private $values = [];

    /**
     * Checks whether there is an entry stored for the given key.
     *
     * @param non-empty-string $key
     *
     * @throws \InvalidArgumentException
     */
    public function has(string $key): bool
    {
        $this->assertNotEmptyKey($key);

        return isset($this->values[$key]);
    }

    /**
     * Returns the entry stored for the given key, and throws an exception if the value does not exist
     * (which helps keep the return type simple).
     *
     * @param non-empty-string $key
     *
     * @throws \BadMethodCallException
     */
    public function get(string $key): string
    {
        if (!$this->has($key)) {
            throw new \BadMethodCallException('You can only call `get` with a key for an existing value.', 1625996246);
        }

        return $this->values[$key];
    }

    /**
     * Sets or overwrites an entry.
     *
     * @param non-empty-string $key
     *
     * @throws \InvalidArgumentException
     */
    public function set(string $key, string $value): void
    {
        $this->assertNotEmptyKey($key);

        $this->values[$key] = $value;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function assertNotEmptyKey(string $key): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Please provide a non-empty key.', 1625995840);
        }
    }
}
