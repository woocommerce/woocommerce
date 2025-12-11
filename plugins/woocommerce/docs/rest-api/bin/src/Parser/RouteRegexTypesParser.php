<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Parser;

/**
 * Parser for route-regex-types.md configuration file.
 */
final class RouteRegexTypesParser
{
    /**
     * @var array<string, string> Cached pattern-to-type mappings
     */
    private array $patterns = [];

    /**
     * @var bool Whether the file has been loaded
     */
    private bool $loaded = false;

    /**
     * @param string $filePath Path to the route-regex-types.md file
     */
    public function __construct(
        private readonly string $filePath
    ) {
    }

    /**
     * Load and parse the configuration file.
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if (!file_exists($this->filePath)) {
            return;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return;
        }

        // Parse markdown table rows
        // Looking for lines like: | `pattern` | type |
        preg_match_all('/^\|\s*`([^`]+)`\s*\|\s*(\w+)\s*\|/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $pattern = $match[1];
            $type = $match[2];
            $this->patterns[$pattern] = $type;
        }
    }

    /**
     * Get all pattern-to-type mappings.
     *
     * @return array<string, string> Pattern => type mappings
     */
    public function getPatterns(): array
    {
        $this->load();
        return $this->patterns;
    }

    /**
     * Look up the type for a given pattern.
     *
     * @param string $pattern The regex pattern to look up
     * @return string|null The type if found, null otherwise
     */
    public function getTypeForPattern(string $pattern): ?string
    {
        $this->load();

        // Try exact match first
        if (isset($this->patterns[$pattern])) {
            return $this->patterns[$pattern];
        }

        // Try with normalized escaping (remove extra backslashes)
        $normalized = stripslashes($pattern);
        if (isset($this->patterns[$normalized])) {
            return $this->patterns[$normalized];
        }

        // Try with added backslashes
        $escaped = addslashes($pattern);
        if (isset($this->patterns[$escaped])) {
            return $this->patterns[$escaped];
        }

        return null;
    }

    /**
     * Check if a pattern is considered "exotic" (not in the config file).
     *
     * @param string $pattern The regex pattern to check
     * @return bool True if the pattern is exotic
     */
    public function isExotic(string $pattern): bool
    {
        return $this->getTypeForPattern($pattern) === null;
    }
}
