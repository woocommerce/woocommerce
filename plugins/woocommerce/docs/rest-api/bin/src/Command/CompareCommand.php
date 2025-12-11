<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Model\Endpoint;
use WooCommerce\RestApiDocs\Parser\DescriptorParser;
use WooCommerce\RestApiDocs\Parser\SchemaParser;

/**
 * Command to compare schema endpoints with existing descriptors.
 */
final class CompareCommand implements CommandInterface
{
    public function __construct(
        private readonly SchemaParser $schemaParser,
        private readonly DescriptorParser $descriptorParser,
    ) {
    }

    public function getName(): string
    {
        return 'compare';
    }

    public function getDescription(): string
    {
        return 'Compare schema endpoints with existing descriptors';
    }

    public function execute(array $options): int
    {
        $verbose = $options['verbose'] ?? false;
        $quiet = $options['quiet'] ?? false;
        $filter = $options['filter'] ?? null;

        try {
            // Load schema
            $schemaPath = $options['schema'] ?? null;
            $schema = $this->schemaParser->loadSchema($schemaPath);

            if (!$quiet) {
                $stats = $this->schemaParser->getStatistics($schema);
                $this->output("Schema loaded: {$stats['wc_endpoints']} WooCommerce endpoints");
                if ($filter !== null) {
                    $this->output("Filter: {$filter}");
                }
            }

            // Parse endpoints from schema
            $endpoints = $this->schemaParser->parseEndpoints($schema);

            // Build a map of endpoint identifiers (applying filter)
            $schemaIdentifiers = [];
            foreach ($endpoints as $endpoint) {
                if ($this->matchesFilter($endpoint->route, $filter)) {
                    $schemaIdentifiers[$endpoint->getIdentifier()] = $endpoint;
                }
            }

            // Load descriptors
            $descriptors = $this->descriptorParser->loadAll();

            if (!$quiet) {
                $this->output("Loaded " . count($descriptors) . " descriptor files");
            }

            // Build a map of descriptor identifiers (applying filter)
            $descriptorIdentifiers = [];
            $ignoredDescriptors = [];

            foreach ($descriptors as $descriptor) {
                if (!$this->matchesFilter($descriptor->route, $filter)) {
                    continue;
                }

                if ($descriptor->ignore) {
                    $ignoredDescriptors[] = $descriptor;
                }

                foreach ($descriptor->getIdentifiers() as $identifier) {
                    $descriptorIdentifiers[$identifier] = $descriptor;
                }
            }

            // Find missing descriptors
            $missing = [];
            foreach ($schemaIdentifiers as $identifier => $endpoint) {
                if (!isset($descriptorIdentifiers[$identifier])) {
                    $missing[] = $endpoint;
                }
            }

            // Find descriptors without matching schema endpoints (applying filter)
            $orphaned = [];
            foreach ($descriptors as $descriptor) {
                if ($descriptor->ignore) {
                    continue;
                }

                // Apply filter to orphaned check as well
                if (!$this->matchesFilter($descriptor->route, $filter)) {
                    continue;
                }

                foreach ($descriptor->getIdentifiers() as $identifier) {
                    if (!isset($schemaIdentifiers[$identifier])) {
                        $orphaned[] = [
                            'identifier' => $identifier,
                            'descriptor' => $descriptor,
                        ];
                    }
                }
            }

            // Output results
            $this->outputResults($missing, $ignoredDescriptors, $orphaned, $verbose, $quiet);

            // Return appropriate exit code
            if (count($missing) > 0) {
                return 1; // Missing descriptors
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Output comparison results.
     *
     * @param array<Endpoint> $missing Missing endpoints
     * @param array<\WooCommerce\RestApiDocs\Model\EndpointDescriptor> $ignored Ignored descriptors
     * @param array<array{identifier: string, descriptor: \WooCommerce\RestApiDocs\Model\EndpointDescriptor}> $orphaned Orphaned descriptors
     * @param bool $verbose Show verbose output
     * @param bool $quiet Suppress non-error output
     */
    private function outputResults(array $missing, array $ignored, array $orphaned, bool $verbose, bool $quiet): void
    {
        if ($quiet) {
            return;
        }

        $this->output("");

        // Missing descriptors
        if (count($missing) > 0) {
            $this->output("Missing endpoint descriptors (" . count($missing) . "):");
            foreach ($missing as $endpoint) {
                $this->output("  - {$endpoint->verb} {$endpoint->route}");
            }
            $this->output("");
        }

        // Ignored descriptors
        if (count($ignored) > 0 && $verbose) {
            $this->output("Ignored endpoints (" . count($ignored) . "):");
            foreach ($ignored as $descriptor) {
                $verbs = implode(',', $descriptor->verbs);
                $this->output("  - {$verbs} {$descriptor->route}");
            }
            $this->output("");
        }

        // Orphaned descriptors (descriptors without matching schema)
        if (count($orphaned) > 0) {
            $this->output("Orphaned descriptors (no matching schema endpoint) (" . count($orphaned) . "):");
            foreach ($orphaned as $item) {
                $this->output("  - {$item['identifier']}");
                $this->output("    File: {$item['descriptor']->filePath}");
            }
            $this->output("");
        }

        // Summary
        $this->output("Summary:");
        $this->output("  Missing descriptors: " . count($missing));
        $this->output("  Ignored endpoints: " . count($ignored));
        $this->output("  Orphaned descriptors: " . count($orphaned));
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
