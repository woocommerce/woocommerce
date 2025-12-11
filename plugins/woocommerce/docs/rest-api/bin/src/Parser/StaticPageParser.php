<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Parser;

use RuntimeException;
use WooCommerce\RestApiDocs\Model\StaticPage;

/**
 * Parser for static markdown pages.
 */
final class StaticPageParser
{
    /**
     * @param string $pagesDir The directory containing static page markdown files
     */
    public function __construct(
        private readonly string $pagesDir,
    ) {
    }

    /**
     * Load all static pages from the pages directory.
     *
     * @return array<StaticPage> List of parsed static pages, sorted by order
     * @throws RuntimeException If a page has an invalid header
     */
    public function loadAll(): array
    {
        $pages = [];

        if (!is_dir($this->pagesDir)) {
            return $pages;
        }

        $files = glob($this->pagesDir . '/*.md');

        foreach ($files as $filePath) {
            $page = $this->parseFile($filePath);
            $pages[] = $page;
        }

        // Sort by order (index page has order 0)
        usort($pages, fn($a, $b) => $a->order <=> $b->order);

        return $pages;
    }

    /**
     * Parse a single static page file.
     *
     * @param string $filePath The path to the markdown file
     * @return StaticPage The parsed static page
     * @throws RuntimeException If the file cannot be read or has invalid header
     */
    public function parseFile(string $filePath): StaticPage
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Failed to read static page file: {$filePath}");
        }

        $filename = basename($filePath, '.md');
        $isIndex = $filename === 'index';

        // Index page doesn't have a header
        if ($isIndex) {
            return new StaticPage(
                filePath: $filePath,
                slug: 'index',
                title: 'Home',
                order: 0,
                content: trim($content),
                isIndex: true,
            );
        }

        return $this->parseWithHeader($content, $filePath, $filename);
    }

    /**
     * Parse a page that requires a header.
     *
     * @param string $content The file content
     * @param string $filePath The file path (for error messages)
     * @param string $filename The filename without extension
     * @return StaticPage The parsed static page
     * @throws RuntimeException If header is missing or invalid
     */
    private function parseWithHeader(string $content, string $filePath, string $filename): StaticPage
    {
        $lines = explode("\n", $content);
        $header = [];
        $contentLines = [];
        $inHeader = true;
        $headerLineCount = 0;

        foreach ($lines as $line) {
            // Header is a markdown table with | key | value | format
            if ($inHeader && str_starts_with(trim($line), '|')) {
                $headerLineCount++;

                // Skip table header rows (first two lines are the table format)
                if ($headerLineCount <= 2) {
                    continue;
                }

                $parsed = $this->parseTableRow($line);
                if ($parsed !== null) {
                    [$key, $value] = $parsed;
                    $header[strtolower(trim($key))] = trim($value);
                }
            } else {
                $inHeader = false;
                $contentLines[] = $line;
            }
        }

        // Validate required fields
        if (!isset($header['title']) || $header['title'] === '') {
            throw new RuntimeException("Static page missing 'title' in header: {$filePath}");
        }

        if (!isset($header['order']) || $header['order'] === '') {
            throw new RuntimeException("Static page missing 'order' in header: {$filePath}");
        }

        $order = (int) $header['order'];
        if ($order < 1) {
            throw new RuntimeException("Static page 'order' must be >= 1: {$filePath}");
        }

        return new StaticPage(
            filePath: $filePath,
            slug: $filename,
            title: $header['title'],
            order: $order,
            content: trim(implode("\n", $contentLines)),
            isIndex: false,
        );
    }

    /**
     * Parse a markdown table row.
     *
     * @param string $line The table row line
     * @return array{0: string, 1: string}|null The key-value pair, or null if invalid
     */
    private function parseTableRow(string $line): ?array
    {
        // Format: | key | value |
        $parts = explode('|', $line);

        // Should have at least 3 parts (empty, key, value, maybe empty)
        if (count($parts) < 3) {
            return null;
        }

        $key = trim($parts[1]);
        $value = trim($parts[2]);

        // Skip separator rows (like |---|---|)
        if (str_contains($key, '-') && preg_match('/^-+$/', $key)) {
            return null;
        }

        // Skip empty keys
        if ($key === '') {
            return null;
        }

        return [$key, $value];
    }
}
