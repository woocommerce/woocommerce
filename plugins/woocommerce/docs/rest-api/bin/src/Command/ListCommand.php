<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Parser\DescriptorParser;
use WooCommerce\RestApiDocs\Parser\SchemaParser;

/**
 * Command to list endpoints from the schema with their status.
 */
final class ListCommand implements CommandInterface
{
    public function __construct(
        private readonly SchemaParser $schemaParser,
        private readonly DescriptorParser $descriptorParser,
    ) {
    }

    public function getName(): string
    {
        return 'list';
    }

    public function getDescription(): string
    {
        return 'List endpoints from the schema with their status';
    }

    public function execute(array $options): int
    {
        $schemaPath = $options['schema'] ?? null;
        $filter = $options['filter'] ?? null;
        $schemaFilter = $options['schema-filter'] ?? null;
        $descriptorFilter = $options['descriptor-filter'] ?? null;

        try {
            // Load schema
            $schema = $this->schemaParser->loadSchema($schemaPath);
            $routes = $schema['routes'] ?? [];

            // Load all descriptors
            $allDescriptors = $this->descriptorParser->loadAll();

            // Build descriptor lookup map (route+verbs -> descriptor)
            $descriptorMap = [];
            foreach ($allDescriptors as $descriptor) {
                $key = $this->normalizeRoute($descriptor->route);
                $descriptorMap[$key] = $descriptor;
            }

            // Build the endpoint list
            $endpoints = [];

            foreach ($routes as $route => $routeData) {
                // Skip non-WooCommerce routes
                if (!str_starts_with($route, '/wc/')) {
                    continue;
                }

                // Apply route filter
                if ($filter !== null && !$this->matchesFilter($route, $filter)) {
                    continue;
                }

                // Determine schema status
                $schemaStatus = $this->getSchemaStatus($routeData);

                // Determine descriptor status
                $normalizedRoute = $this->normalizeRoute($route);
                $descriptorStatus = $this->getDescriptorStatus($normalizedRoute, $descriptorMap);

                // Apply schema filter
                if ($schemaFilter !== null && !$this->matchesSchemaFilter($schemaStatus, $schemaFilter)) {
                    continue;
                }

                // Apply descriptor filter
                if ($descriptorFilter !== null && !$this->matchesDescriptorFilter($descriptorStatus, $descriptorFilter)) {
                    continue;
                }

                $endpoints[] = [
                    'route' => $route,
                    'schema' => $schemaStatus,
                    'descriptor' => $descriptorStatus,
                ];
            }

            // Sort by route
            usort($endpoints, fn($a, $b) => strcmp($a['route'], $b['route']));

            // Output as markdown table
            $this->outputTable($endpoints);

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get the schema status for a route.
     *
     * @param array $routeData The route data from schema
     * @return string "yes", "no", or "?"
     */
    private function getSchemaStatus(array $routeData): string
    {
        if (!array_key_exists('schema', $routeData)) {
            return '?'; // Not fetched yet
        }

        $schema = $routeData['schema'];

        // Empty object or empty array means fetched but no schema
        if ($schema === null) {
            return '?';
        }

        if (is_object($schema) && empty((array) $schema)) {
            return 'no';
        }

        if (is_array($schema) && empty($schema)) {
            return 'no';
        }

        return 'yes';
    }

    /**
     * Get the descriptor status for a route.
     *
     * @param string $normalizedRoute The normalized route
     * @param array $descriptorMap Map of routes to descriptors
     * @return string "yes", "no", or "ignored"
     */
    private function getDescriptorStatus(string $normalizedRoute, array $descriptorMap): string
    {
        if (!isset($descriptorMap[$normalizedRoute])) {
            return 'no';
        }

        $descriptor = $descriptorMap[$normalizedRoute];
        return $descriptor->ignore ? 'ignored' : 'yes';
    }

    /**
     * Normalize a route by removing regex patterns.
     *
     * @param string $route The route
     * @return string Normalized route
     */
    private function normalizeRoute(string $route): string
    {
        // Keep the route as-is for matching
        return $route;
    }

    /**
     * Check if a route matches the filter pattern.
     *
     * @param string $route The route to check
     * @param string $filter The filter pattern
     * @return bool True if matches
     */
    private function matchesFilter(string $route, string $filter): bool
    {
        // Try as regex first
        if (@preg_match('#' . $filter . '#', $route) === 1) {
            return true;
        }

        // Fall back to simple string contains
        return str_contains($route, $filter);
    }

    /**
     * Check if schema status matches the filter.
     *
     * @param string $status The schema status
     * @param string $filter The filter (yes, no, ?, no?)
     * @return bool True if matches
     */
    private function matchesSchemaFilter(string $status, string $filter): bool
    {
        return match ($filter) {
            'yes' => $status === 'yes',
            'no' => $status === 'no',
            '?' => $status === '?',
            'no?' => $status === 'no' || $status === '?',
            default => true,
        };
    }

    /**
     * Check if descriptor status matches the filter.
     *
     * @param string $status The descriptor status
     * @param string $filter The filter (yes, no, ignored, yi)
     * @return bool True if matches
     */
    private function matchesDescriptorFilter(string $status, string $filter): bool
    {
        return match ($filter) {
            'yes' => $status === 'yes',
            'no' => $status === 'no',
            'ignored' => $status === 'ignored',
            'yi' => $status === 'yes' || $status === 'ignored',
            default => true,
        };
    }

    /**
     * Output the endpoints as a markdown table.
     *
     * @param array $endpoints The endpoints to output
     */
    private function outputTable(array $endpoints): void
    {
        if (empty($endpoints)) {
            $this->output("No endpoints found matching the criteria.");
            return;
        }

        // Calculate column widths
        $routeWidth = max(5, ...array_map(fn($e) => strlen($e['route']), $endpoints));
        $schemaWidth = 6; // "Schema"
        $descriptorWidth = 10; // "Descriptor"

        // Header
        $this->output(sprintf(
            "| %-{$routeWidth}s | %-{$schemaWidth}s | %-{$descriptorWidth}s |",
            'Route',
            'Schema',
            'Descriptor'
        ));
        $this->output(sprintf(
            "|-%s-|-%s-|-%s-|",
            str_repeat('-', $routeWidth),
            str_repeat('-', $schemaWidth),
            str_repeat('-', $descriptorWidth)
        ));

        // Rows
        foreach ($endpoints as $endpoint) {
            $this->output(sprintf(
                "| %-{$routeWidth}s | %-{$schemaWidth}s | %-{$descriptorWidth}s |",
                $endpoint['route'],
                $endpoint['schema'],
                $endpoint['descriptor']
            ));
        }

        $this->output("");
        $this->output(sprintf("Total: %d endpoints", count($endpoints)));
    }

    /**
     * Output a message.
     */
    private function output(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Output an error message.
     */
    private function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
