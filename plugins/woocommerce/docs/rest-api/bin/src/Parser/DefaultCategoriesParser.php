<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Parser;

use RuntimeException;

/**
 * Parser for default-categories.md file.
 */
final class DefaultCategoriesParser
{
    private const DEFAULT_CATEGORY = 'UNCATEGORIZED';

    /** @var array<array{pattern: string, category: string}> */
    private array $rules = [];

    /**
     * @param string $filePath Path to the default-categories.md file
     */
    public function __construct(
        private readonly string $filePath,
    ) {
        $this->loadRules();
    }

    /**
     * Load rules from the file.
     */
    private function loadRules(): void
    {
        if (!file_exists($this->filePath)) {
            return;
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new RuntimeException("Failed to read default categories file: {$this->filePath}");
        }

        $lines = explode("\n", $content);
        $inTable = false;
        $tableLineCount = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip empty lines and non-table content
            if ($trimmed === '' || !str_starts_with($trimmed, '|')) {
                $inTable = false;
                $tableLineCount = 0;
                continue;
            }

            $inTable = true;
            $tableLineCount++;

            // Skip table header rows (first two lines)
            if ($tableLineCount <= 2) {
                continue;
            }

            $parsed = $this->parseTableRow($trimmed);
            if ($parsed !== null) {
                $this->rules[] = $parsed;
            }
        }
    }

    /**
     * Parse a table row into a rule.
     *
     * @param string $line The table row
     * @return array{pattern: string, category: string}|null The rule or null if invalid
     */
    private function parseTableRow(string $line): ?array
    {
        $parts = explode('|', $line);

        if (count($parts) < 3) {
            return null;
        }

        $pattern = trim($parts[1]);
        $category = trim($parts[2]);

        // Skip separator rows and empty patterns
        if ($pattern === '' || preg_match('/^-+$/', $pattern)) {
            return null;
        }

        return [
            'pattern' => $pattern,
            'category' => $category,
        ];
    }

    /**
     * Get the category for a route.
     *
     * @param string $route The route to categorize
     * @return string The category, or UNCATEGORIZED if no match
     */
    public function getCategoryForRoute(string $route): string
    {
        foreach ($this->rules as $rule) {
            $regex = '#^' . $rule['pattern'] . '$#';

            if (preg_match($regex, $route)) {
                return $rule['category'];
            }
        }

        return self::DEFAULT_CATEGORY;
    }

    /**
     * Get all loaded rules.
     *
     * @return array<array{pattern: string, category: string}>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Check if the file exists.
     */
    public function exists(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * Get the default category constant.
     */
    public static function getDefaultCategory(): string
    {
        return self::DEFAULT_CATEGORY;
    }
}
