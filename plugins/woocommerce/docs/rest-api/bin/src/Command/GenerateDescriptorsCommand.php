<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Model\Endpoint;
use WooCommerce\RestApiDocs\Parser\DefaultCategoriesParser;
use WooCommerce\RestApiDocs\Parser\DescriptorParser;
use WooCommerce\RestApiDocs\Parser\SchemaParser;
use WooCommerce\RestApiDocs\Util\RouteFormatter;

/**
 * Command to generate missing endpoint descriptors.
 */
final class GenerateDescriptorsCommand implements CommandInterface
{
    public function __construct(
        private readonly SchemaParser $schemaParser,
        private readonly DescriptorParser $descriptorParser,
        private readonly DefaultCategoriesParser $categoriesParser,
        private readonly string $descriptorsDir,
    ) {
    }

    public function getName(): string
    {
        return 'generate-descriptors';
    }

    public function getDescription(): string
    {
        return 'Generate missing endpoint descriptor files';
    }

    public function execute(array $options): int
    {
        $verbose = $options['verbose'] ?? false;
        $quiet = $options['quiet'] ?? false;
        $dryRun = $options['dry-run'] ?? false;
        $filter = $options['filter'] ?? null;

        try {
            // Load schema
            $schemaPath = $options['schema'] ?? null;
            $schema = $this->schemaParser->loadSchema($schemaPath);

            // Parse endpoints from schema
            $endpoints = $this->schemaParser->parseEndpoints($schema);

            // Load existing descriptors
            $descriptors = $this->descriptorParser->loadAll();

            // Build set of existing identifiers
            $existingIdentifiers = [];
            foreach ($descriptors as $descriptor) {
                foreach ($descriptor->getIdentifiers() as $identifier) {
                    $existingIdentifiers[$identifier] = true;
                }
            }

            // Find missing endpoints (applying filter)
            $missing = [];
            foreach ($endpoints as $endpoint) {
                if (!$this->matchesFilter($endpoint->route, $filter)) {
                    continue;
                }
                $identifier = $endpoint->getIdentifier();
                if (!isset($existingIdentifiers[$identifier])) {
                    $missing[] = $endpoint;
                }
            }

            if (count($missing) === 0) {
                if (!$quiet) {
                    $this->output("All endpoints have descriptors. Nothing to generate.");
                    if ($filter !== null) {
                        $this->output("(Filter: {$filter})");
                    }
                }
                return 0;
            }

            if (!$quiet) {
                $this->output("Found " . count($missing) . " endpoints without descriptors.");
                if ($filter !== null) {
                    $this->output("Filter: {$filter}");
                }
                if ($dryRun) {
                    $this->output("Dry run - no files will be created.");
                }
            }

            // Group endpoints by route, keeping GET and DELETE separate
            // POST/PUT/PATCH can be grouped together as they typically have similar semantics
            $groups = $this->groupEndpointsForDescriptors($missing);

            // Generate descriptors
            $created = 0;
            $errors = [];

            foreach ($groups as $group) {
                try {
                    $this->generateDescriptor($group, $dryRun, $verbose);
                    $created++;
                } catch (\Exception $e) {
                    $route = $group[0]->route;
                    $verbs = implode(',', array_map(fn($e) => $e->verb, $group));
                    $errors[] = "Failed to create descriptor for {$verbs} {$route}: " . $e->getMessage();
                }
            }

            // Output summary
            if (!$quiet) {
                $this->output("");
                $this->output("Summary:");
                $this->output("  Descriptors created: {$created}");

                if (count($errors) > 0) {
                    $this->output("  Errors: " . count($errors));
                    foreach ($errors as $error) {
                        $this->error("  - {$error}");
                    }
                }
            }

            return count($errors) > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Generate a descriptor file for a group of endpoints (same route, different verbs).
     *
     * @param array<Endpoint> $endpoints Endpoints for the same route
     * @param bool $dryRun If true, don't actually create files
     * @param bool $verbose Show verbose output
     */
    private function generateDescriptor(array $endpoints, bool $dryRun, bool $verbose): void
    {
        $firstEndpoint = $endpoints[0];
        $route = $firstEndpoint->route;

        // Determine category
        $category = $this->categoriesParser->getCategoryForRoute($route);

        // Collect all verbs
        $verbs = array_map(fn(Endpoint $e) => $e->verb, $endpoints);
        sort($verbs);

        // Generate filename
        $filename = RouteFormatter::generateDescriptorFilename($verbs, $route) . '.md';

        // Determine directory based on category
        $categoryPath = str_replace('/', DIRECTORY_SEPARATOR, $category);
        $directory = $this->descriptorsDir . DIRECTORY_SEPARATOR . $categoryPath;
        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;

        // Generate content
        $content = $this->generateDescriptorContent($route, $verbs, $category);

        if ($verbose) {
            $this->output("Creating: {$filePath}");
        }

        if (!$dryRun) {
            // Create directory if needed
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
            }

            // Write file
            if (file_put_contents($filePath, $content) === false) {
                throw new \RuntimeException("Failed to write file: {$filePath}");
            }
        }
    }

    /**
     * Generate the content for a descriptor file.
     *
     * @param string $route The route pattern
     * @param array<string> $verbs The HTTP verbs
     * @param string $category The category
     * @return string The file content
     */
    private function generateDescriptorContent(string $route, array $verbs, string $category): string
    {
        $verbStr = implode(',', $verbs);
        $displayRoute = RouteFormatter::formatForDisplay($route);

        $lines = [
            '|          |                                                              |',
            '|----------|--------------------------------------------------------------|',
            "| category | {$category}",
            "| route    | {$route}",
            "| name     | TODO: Add name for {$displayRoute}",
            "| verb     | {$verbStr}",
            '| ignore   | true',
            '',
            'TODO: Add description for this endpoint.',
            '',
        ];

        return implode("\n", $lines);
    }

    /**
     * Group endpoints for descriptor generation.
     * GET and DELETE get their own descriptors, POST/PUT/PATCH are grouped together.
     *
     * @param array<Endpoint> $endpoints All endpoints to group
     * @return array<array<Endpoint>> Groups of endpoints, each group becomes one descriptor
     */
    private function groupEndpointsForDescriptors(array $endpoints): array
    {
        $groups = [];

        // First, group by route
        $byRoute = [];
        foreach ($endpoints as $endpoint) {
            $byRoute[$endpoint->route][] = $endpoint;
        }

        // Then, for each route, separate GET and DELETE from POST/PUT/PATCH
        foreach ($byRoute as $route => $routeEndpoints) {
            $modifyGroup = []; // POST, PUT, PATCH

            foreach ($routeEndpoints as $endpoint) {
                if ($endpoint->verb === 'GET' || $endpoint->verb === 'DELETE') {
                    // GET and DELETE always get their own descriptor
                    $groups[] = [$endpoint];
                } else {
                    // POST, PUT, PATCH can be grouped
                    $modifyGroup[] = $endpoint;
                }
            }

            // Add the modify group if not empty
            if (count($modifyGroup) > 0) {
                $groups[] = $modifyGroup;
            }
        }

        return $groups;
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

        // Try as regex first (if it looks like a regex)
        if (@preg_match('#' . $filter . '#', $route) === 1) {
            return true;
        }

        // Fall back to simple string contains
        return str_contains($route, $filter);
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
