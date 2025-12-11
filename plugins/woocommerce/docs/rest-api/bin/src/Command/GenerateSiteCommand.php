<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Generator\SiteGenerator;
use WooCommerce\RestApiDocs\Parser\DescriptorParser;
use WooCommerce\RestApiDocs\Parser\SchemaParser;
use WooCommerce\RestApiDocs\Template\TemplateEngine;

/**
 * Command to generate the static documentation site.
 */
final class GenerateSiteCommand implements CommandInterface
{
    public function __construct(
        private readonly SchemaParser $schemaParser,
        private readonly DescriptorParser $descriptorParser,
        private readonly string $templatesDir,
        private readonly string $outputDir,
        private readonly string $assetsDir,
    ) {
    }

    public function getName(): string
    {
        return 'generate-site';
    }

    public function getDescription(): string
    {
        return 'Generate the static documentation site';
    }

    public function execute(array $options): int
    {
        $verbose = $options['verbose'] ?? false;
        $quiet = $options['quiet'] ?? false;
        $excludeIncomplete = $options['exclude-incomplete'] ?? false;
        $reset = $options['reset'] ?? false;
        $incremental = !$reset; // Incremental is now the default
        $filter = $options['filter'] ?? null;

        try {
            // Load schema
            $schemaPath = $options['schema'] ?? null;
            $schema = $this->schemaParser->loadSchema($schemaPath);

            // Parse endpoints from schema
            $endpoints = $this->schemaParser->parseEndpoints($schema);

            if (!$quiet) {
                $this->output("Loaded " . count($endpoints) . " endpoints from schema");
            }

            // Load descriptors
            $allDescriptors = $this->descriptorParser->loadAll();

            // Filter out ignored descriptors for counting
            $activeDescriptors = array_filter($allDescriptors, fn($d) => !$d->ignore);

            // Apply filter for page generation if specified
            $filteredDescriptors = $filter !== null
                ? array_filter($allDescriptors, fn($d) => $this->matchesFilter($d->route, $filter))
                : null;

            if (!$quiet) {
                $this->output("Found " . count($activeDescriptors) . " active endpoint descriptors");
                if ($filter !== null) {
                    $filteredCount = count(array_filter($filteredDescriptors, fn($d) => !$d->ignore));
                    $this->output("Filter: {$filter} ({$filteredCount} matching)");
                }
                if ($excludeIncomplete) {
                    $this->output("Excluding endpoints with incomplete schemas");
                }
                if ($reset) {
                    $this->output("Reset mode: regenerating all content");
                }
                $this->output("");
                $this->output("Generating site...");
            }

            // Create template engine and site generator
            $templateEngine = new TemplateEngine($this->templatesDir);
            $siteGenerator = new SiteGenerator(
                $templateEngine,
                $this->outputDir,
                $this->assetsDir
            );

            // Generate site - always pass all descriptors for sidebar, filtered for page generation
            $result = $siteGenerator->generate(
                array_values($allDescriptors),
                $endpoints,
                $excludeIncomplete,
                $incremental,
                $filteredDescriptors !== null ? array_values($filteredDescriptors) : null
            );

            // Output results
            if (!$quiet) {
                $this->output("");
                $this->output("Site generated successfully!");
                $this->output("  Pages created: {$result['pages']}");
                $this->output("  Output directory: {$this->outputDir}");

                if (count($result['errors']) > 0) {
                    $this->output("");
                    $this->output("Errors (" . count($result['errors']) . "):");
                    foreach ($result['errors'] as $error) {
                        $this->error("  - {$error}");
                    }
                }
            }

            return count($result['errors']) > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            if ($verbose) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
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
