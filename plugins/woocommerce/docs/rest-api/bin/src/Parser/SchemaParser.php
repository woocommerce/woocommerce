<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Parser;

use RuntimeException;
use WooCommerce\RestApiDocs\Model\Endpoint;
use WooCommerce\RestApiDocs\Util\HttpClient;

/**
 * Parser for WordPress REST API schema.
 */
final class SchemaParser
{
    private const CACHE_FILENAME = 'rest-api-schema.json';
    private const WC_ROUTE_PREFIX = '/wc/';

    /**
     * @param string $tempDir The directory for caching the schema
     */
    public function __construct(
        private readonly string $tempDir,
    ) {
    }

    /**
     * Load the schema from a local file.
     *
     * @param string|null $schemaPath Path to schema file (uses default if null)
     * @return array<string, mixed> The raw schema data
     * @throws RuntimeException If no schema is available
     */
    public function loadSchema(?string $schemaPath = null): array
    {
        $path = $schemaPath ?? $this->getCachePath();

        if (!file_exists($path)) {
            throw new RuntimeException(
                "Schema file not found: {$path}\n" .
                "Run 'fetch-schema --url=<wordpress-url>' first to fetch the schema."
            );
        }

        return $this->loadSchemaFromFile($path);
    }

    /**
     * Fetch schema from URL and save to file.
     *
     * @param string $url The WordPress site URL
     * @param string $outputPath Path to save the schema
     * @param string|null $authToken Optional authentication token
     * @param callable|null $progressCallback Optional callback for progress updates
     * @param string|null $filter Optional filter for OPTIONS requests (regex or substring)
     * @param bool $incremental Merge with existing schema file instead of replacing
     * @return array<string, mixed> The fetched schema data
     */
    public function fetchAndSaveSchema(
        string $url,
        string $outputPath,
        ?string $authToken = null,
        ?callable $progressCallback = null,
        ?string $filter = null,
        bool $incremental = false
    ): array {
        // Load existing schema if incremental mode
        $existingSchema = null;
        if ($incremental && file_exists($outputPath)) {
            if ($progressCallback) {
                $progressCallback("Loading existing schema from: {$outputPath}", false);
            }
            $existingSchema = $this->loadSchemaFromFile($outputPath);
        }

        $schema = HttpClient::fetchSchema($url, $authToken);

        // Fetch response schemas via OPTIONS requests
        $schema = $this->enrichWithResponseSchemas($schema, $url, $authToken, $progressCallback, $filter);

        // Merge with existing schema if incremental
        if ($existingSchema !== null) {
            $schema = $this->mergeSchemas($existingSchema, $schema, $filter, $progressCallback);
        }

        $this->saveSchemaToFile($schema, $outputPath);

        return $schema;
    }

    /**
     * Merge two schemas, updating routes from the new schema into the existing one.
     *
     * @param array<string, mixed> $existingSchema The existing schema
     * @param array<string, mixed> $newSchema The new schema with updates
     * @param string|null $filter The filter used (only merge routes matching the filter)
     * @param callable|null $progressCallback Optional progress callback
     * @return array<string, mixed> The merged schema
     */
    private function mergeSchemas(
        array $existingSchema,
        array $newSchema,
        ?string $filter,
        ?callable $progressCallback
    ): array {
        $merged = $existingSchema;
        $updatedCount = 0;

        foreach ($newSchema['routes'] ?? [] as $route => $routeData) {
            // Only merge routes that match the filter (if specified)
            if ($filter !== null && !$this->matchesFilter($route, $filter)) {
                continue;
            }

            // Update or add the route
            $merged['routes'][$route] = $routeData;
            $updatedCount++;
        }

        if ($progressCallback) {
            $progressCallback("Merged {$updatedCount} routes into existing schema", false);
        }

        return $merged;
    }

    /**
     * Parse the schema and extract WooCommerce endpoints.
     *
     * @param array<string, mixed> $schema The raw schema data
     * @return array<Endpoint> List of endpoints
     */
    public function parseEndpoints(array $schema): array
    {
        $endpoints = [];
        $routes = $schema['routes'] ?? [];

        foreach ($routes as $route => $routeData) {
            // Skip non-WooCommerce routes
            if (!str_starts_with($route, self::WC_ROUTE_PREFIX)) {
                continue;
            }

            $endpointDefinitions = $routeData['endpoints'] ?? [];
            $responseSchema = $routeData['schema'] ?? null;

            // Treat empty schema (fetched but no schema available) as null
            if (is_object($responseSchema) || (is_array($responseSchema) && empty($responseSchema))) {
                $responseSchema = null;
            }

            foreach ($endpointDefinitions as $definition) {
                $methods = $definition['methods'] ?? [];
                $args = $definition['args'] ?? [];

                foreach ($methods as $method) {
                    $endpoints[] = new Endpoint(
                        route: $route,
                        verb: $method,
                        args: $args,
                        schema: $responseSchema,
                        description: $this->extractDescription($definition),
                    );
                }
            }
        }

        return $endpoints;
    }

    /**
     * Get the path to the cached schema file.
     */
    public function getCachePath(): string
    {
        return $this->tempDir . '/' . self::CACHE_FILENAME;
    }

    /**
     * Check if a cached schema exists.
     */
    public function hasCachedSchema(): bool
    {
        return file_exists($this->getCachePath());
    }

    /**
     * Save the schema to a file.
     *
     * @param array<string, mixed> $schema The schema to save
     * @param string $path The file path
     */
    private function saveSchemaToFile(array $schema, string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode schema as JSON');
        }

        $result = file_put_contents($path, $json);

        if ($result === false) {
            throw new RuntimeException("Failed to write schema file: {$path}");
        }
    }

    /**
     * Load a schema from a file.
     *
     * @param string $path The file path
     * @return array<string, mixed> The schema data
     */
    private function loadSchemaFromFile(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Failed to read schema file: {$path}");
        }

        $schema = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf('Failed to parse schema file: %s', json_last_error_msg())
            );
        }

        return $schema;
    }

    /**
     * Extract description from an endpoint definition.
     *
     * @param array<string, mixed> $definition The endpoint definition
     * @return string|null The description if found
     */
    private function extractDescription(array $definition): ?string
    {
        // WordPress schema doesn't typically have endpoint-level descriptions
        // but some plugins might add them
        return $definition['description'] ?? null;
    }

    /**
     * Check if a route matches the filter pattern.
     *
     * @param string $route The route to check
     * @param string|null $filter The filter pattern (regex or simple string)
     * @return bool True if the route matches or no filter is set
     */
    private function matchesFilter(string $route, ?string $filter): bool
    {
        if ($filter === null) {
            return true;
        }

        // Try as regex first
        if (@preg_match('#' . $filter . '#', $route) === 1) {
            return true;
        }

        // Fall back to simple string contains
        return str_contains($route, $filter);
    }

    /**
     * Enrich schema with response schemas fetched via OPTIONS requests.
     *
     * @param array<string, mixed> $schema The base schema
     * @param string $baseUrl The WordPress site URL
     * @param string|null $authToken Optional authentication token
     * @param callable|null $progressCallback Optional progress callback
     * @param string|null $filter Optional filter for routes (regex or substring)
     * @return array<string, mixed> The enriched schema
     */
    private function enrichWithResponseSchemas(
        array $schema,
        string $baseUrl,
        ?string $authToken,
        ?callable $progressCallback,
        ?string $filter = null
    ): array {
        $routes = $schema['routes'] ?? [];
        $wcRoutes = array_filter(
            array_keys($routes),
            fn($route) => str_starts_with($route, self::WC_ROUTE_PREFIX)
                && $this->matchesFilter($route, $filter)
        );

        $total = count($wcRoutes);
        $current = 0;
        $fetched = 0;

        if ($progressCallback) {
            $progressCallback("Fetching response schemas for {$total} WooCommerce routes...", false);
        }

        foreach ($wcRoutes as $route) {
            $current++;

            if ($progressCallback) {
                $progressCallback("Fetching endpoint {$current}/{$total}...", true);
            }

            // Skip if already has schema
            if (!empty($schema['routes'][$route]['schema'])) {
                continue;
            }

            // Convert route pattern to actual URL
            $endpointUrl = $this->routePatternToUrl($route, $baseUrl);

            // Fetch schema via OPTIONS
            $responseSchema = HttpClient::fetchEndpointSchema($endpointUrl, $authToken);

            if ($responseSchema !== null) {
                $schema['routes'][$route]['schema'] = $responseSchema;
                $fetched++;
            } else {
                // Mark as fetched but no schema available (empty object)
                // This distinguishes "no schema" from "not fetched yet"
                $schema['routes'][$route]['schema'] = new \stdClass();
            }
        }

        if ($progressCallback) {
            // Clear the inline progress and show completion message
            $progressCallback("Fetched {$fetched} response schemas from {$total} endpoints", false);
        }

        return $schema;
    }

    /**
     * Convert a route pattern to an actual URL with dummy parameter values.
     *
     * @param string $route The route pattern (e.g., /wc/v3/products/(?P<id>[\d]+))
     * @param string $baseUrl The WordPress site base URL
     * @return string The full URL with dummy values
     */
    private function routePatternToUrl(string $route, string $baseUrl): string
    {
        // Replace regex patterns with dummy values
        // (?P<name>[\d]+) -> 0 (numeric ID)
        // (?P<name>[a-z0-9_-]+) -> dummy (slug)
        // (?P<name>.+) -> dummy (generic string)

        $url = $route;

        // Numeric patterns (IDs)
        $url = preg_replace('/\(\?P<[^>]+>\[\\\\d\]\+\)/', '0', $url);

        // Alphanumeric patterns (slugs)
        $url = preg_replace('/\(\?P<[^>]+>\[a-z0-9_\-\]\+\)/', 'dummy', $url);
        $url = preg_replace('/\(\?P<[^>]+>\[\\\\w\]\+\)/', 'dummy', $url);

        // Generic patterns
        $url = preg_replace('/\(\?P<[^>]+>[^)]+\)/', 'dummy', $url);

        return rtrim($baseUrl, '/') . '/wp-json' . $url;
    }

    /**
     * Get statistics about the schema.
     *
     * @param array<string, mixed> $schema The raw schema data
     * @return array<string, int> Statistics
     */
    public function getStatistics(array $schema): array
    {
        $totalRoutes = 0;
        $wcRoutes = 0;
        $totalEndpoints = 0;
        $wcEndpoints = 0;

        $routes = $schema['routes'] ?? [];

        foreach ($routes as $route => $routeData) {
            $totalRoutes++;
            $isWc = str_starts_with($route, self::WC_ROUTE_PREFIX);

            if ($isWc) {
                $wcRoutes++;
            }

            $endpointDefinitions = $routeData['endpoints'] ?? [];

            foreach ($endpointDefinitions as $definition) {
                $methodCount = count($definition['methods'] ?? []);
                $totalEndpoints += $methodCount;

                if ($isWc) {
                    $wcEndpoints += $methodCount;
                }
            }
        }

        return [
            'total_routes' => $totalRoutes,
            'wc_routes' => $wcRoutes,
            'total_endpoints' => $totalEndpoints,
            'wc_endpoints' => $wcEndpoints,
        ];
    }
}
