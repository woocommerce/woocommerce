<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Parser;

use RuntimeException;
use WooCommerce\RestApiDocs\Model\EndpointDescriptor;

/**
 * Parser for endpoint descriptor markdown files.
 */
final class DescriptorParser
{
    /**
     * @param string $descriptorsDir The directory containing descriptor files
     */
    public function __construct(
        private readonly string $descriptorsDir,
    ) {
    }

    /**
     * Load all descriptor files from the descriptors directory.
     *
     * @return array<EndpointDescriptor> List of parsed descriptors
     */
    public function loadAll(): array
    {
        $descriptors = [];

        if (!is_dir($this->descriptorsDir)) {
            return $descriptors;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->descriptorsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $descriptor = $this->parseFile($file->getPathname());
                if ($descriptor !== null) {
                    $descriptors[] = $descriptor;
                }
            }
        }

        return $descriptors;
    }

    /**
     * Parse a single descriptor file.
     *
     * @param string $filePath The path to the descriptor file
     * @return EndpointDescriptor|null The parsed descriptor, or null if invalid
     * @throws RuntimeException If the file cannot be read
     */
    public function parseFile(string $filePath): ?EndpointDescriptor
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Failed to read descriptor file: {$filePath}");
        }

        return $this->parseContent($content, $filePath);
    }

    /**
     * Parse descriptor content.
     *
     * @param string $content The file content
     * @param string $filePath The file path (for reference)
     * @return EndpointDescriptor|null The parsed descriptor
     */
    public function parseContent(string $content, string $filePath): ?EndpointDescriptor
    {
        $lines = explode("\n", $content);
        $header = [];
        $descriptionLines = [];
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
                $descriptionLines[] = $line;
            }
        }

        // Validate required fields
        $requiredFields = ['category', 'route', 'name', 'verb'];
        foreach ($requiredFields as $field) {
            if (!isset($header[$field]) || $header[$field] === '') {
                return null;
            }
        }

        // Parse verbs
        $verbs = array_map(
            fn(string $v) => strtoupper(trim($v)),
            explode(',', $header['verb'])
        );

        // Parse ignore flag
        $ignore = isset($header['ignore']) &&
            in_array(strtolower($header['ignore']), ['true', 'yes', '1'], true);

        // Parse public flag (no auth required)
        $public = isset($header['public']) &&
            in_array(strtolower($header['public']), ['true', 'yes', '1'], true);

        // Clean up description
        $description = trim(implode("\n", $descriptionLines));

        return new EndpointDescriptor(
            filePath: $filePath,
            category: $header['category'],
            route: $header['route'],
            name: $header['name'],
            verbs: $verbs,
            description: $description,
            ignore: $ignore,
            public: $public,
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

    /**
     * Validate a descriptor and return any errors.
     *
     * @param EndpointDescriptor $descriptor The descriptor to validate
     * @param array<string> $validRoutes List of valid routes from schema
     * @return array<string> List of validation errors
     */
    public function validate(EndpointDescriptor $descriptor, array $validRoutes): array
    {
        $errors = [];

        // Check category depth
        if ($descriptor->getCategoryDepth() > 4) {
            $errors[] = "Category depth exceeds 4 levels: {$descriptor->category}";
        }

        // Check verbs are valid
        $validVerbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        foreach ($descriptor->verbs as $verb) {
            if (!in_array($verb, $validVerbs, true)) {
                $errors[] = "Invalid HTTP verb: {$verb}";
            }
        }

        // Check route exists in schema
        if (!in_array($descriptor->route, $validRoutes, true)) {
            $errors[] = "Route not found in schema: {$descriptor->route}";
        }

        // Check name is not a placeholder
        if (str_starts_with($descriptor->name, 'TODO:')) {
            $errors[] = "Name is still a placeholder: {$descriptor->name}";
        }

        return $errors;
    }

    /**
     * Get validation warnings (non-critical issues).
     *
     * @param EndpointDescriptor $descriptor The descriptor to check
     * @return array<string> List of warnings
     */
    public function getWarnings(EndpointDescriptor $descriptor): array
    {
        $warnings = [];

        // Empty description
        if (trim($descriptor->description) === '') {
            $warnings[] = 'Description is empty';
        }

        return $warnings;
    }
}
