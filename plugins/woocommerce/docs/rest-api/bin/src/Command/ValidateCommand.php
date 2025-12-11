<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Model\EndpointDescriptor;
use WooCommerce\RestApiDocs\Parser\DescriptorParser;
use WooCommerce\RestApiDocs\Parser\SchemaParser;

/**
 * Command to validate endpoint descriptor files.
 */
final class ValidateCommand implements CommandInterface
{
    public function __construct(
        private readonly SchemaParser $schemaParser,
        private readonly DescriptorParser $descriptorParser,
    ) {
    }

    public function getName(): string
    {
        return 'validate';
    }

    public function getDescription(): string
    {
        return 'Validate all endpoint descriptor files';
    }

    public function execute(array $options): int
    {
        $verbose = $options['verbose'] ?? false;
        $quiet = $options['quiet'] ?? false;
        $filter = $options['filter'] ?? null;

        try {
            // Load schema to get valid routes
            $schemaPath = $options['schema'] ?? null;
            $schema = $this->schemaParser->loadSchema($schemaPath);

            // Get list of valid routes
            $endpoints = $this->schemaParser->parseEndpoints($schema);
            $validRoutes = array_unique(array_map(fn($e) => $e->route, $endpoints));

            // Load all descriptor files
            $allDescriptors = $this->descriptorParser->loadAll();

            // Apply filter if specified
            $filteredDescriptors = array_filter(
                $allDescriptors,
                fn($d) => $this->matchesFilter($d->route, $filter)
            );

            // Separate ignored and active descriptors
            $ignoredDescriptors = array_filter($filteredDescriptors, fn($d) => $d->ignore);
            $descriptors = array_filter($filteredDescriptors, fn($d) => !$d->ignore);

            if (!$quiet) {
                $this->output("Validating " . count($descriptors) . " descriptor files...");
                if ($filter !== null) {
                    $this->output("Filter: {$filter}");
                }
                $this->output("");
            }

            // Validate each descriptor
            $allErrors = [];
            $allWarnings = [];
            $validCount = 0;

            // Track duplicates
            $seenIdentifiers = [];

            foreach ($descriptors as $descriptor) {
                $fileErrors = [];
                $fileWarnings = [];

                // Get validation errors
                $errors = $this->descriptorParser->validate($descriptor, $validRoutes);
                $warnings = $this->descriptorParser->getWarnings($descriptor);

                // Check for duplicates
                foreach ($descriptor->getIdentifiers() as $identifier) {
                    if (isset($seenIdentifiers[$identifier])) {
                        $errors[] = "Duplicate endpoint: {$identifier} (also in {$seenIdentifiers[$identifier]})";
                    } else {
                        $seenIdentifiers[$identifier] = $descriptor->filePath;
                    }
                }

                if (count($errors) > 0) {
                    $allErrors[$descriptor->filePath] = $errors;
                }

                if (count($warnings) > 0) {
                    $allWarnings[$descriptor->filePath] = $warnings;
                }

                if (count($errors) === 0) {
                    $validCount++;
                }
            }

            // Also check for malformed files that couldn't be parsed
            $parseErrors = $this->findParseErrors();
            foreach ($parseErrors as $file => $error) {
                $allErrors[$file] = [$error];
            }

            // Output results
            $this->outputResults($allErrors, $allWarnings, $validCount, count($descriptors), $ignoredDescriptors, $verbose, $quiet);

            // Return appropriate exit code
            if (count($allErrors) > 0) {
                return 2; // Validation errors
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Find files that failed to parse.
     *
     * @return array<string, string> Map of file path to error message
     */
    private function findParseErrors(): array
    {
        $errors = [];

        // This would require scanning the directory again and checking
        // which files failed to parse. For now, we'll rely on the parser
        // throwing exceptions for malformed files.

        return $errors;
    }

    /**
     * Output validation results.
     *
     * @param array<string, array<string>> $errors Map of file to errors
     * @param array<string, array<string>> $warnings Map of file to warnings
     * @param int $validCount Number of valid descriptors
     * @param int $totalCount Total number of descriptors
     * @param array<\WooCommerce\RestApiDocs\Model\EndpointDescriptor> $ignoredDescriptors Ignored descriptors
     * @param bool $verbose Show verbose output
     * @param bool $quiet Suppress non-error output
     */
    private function outputResults(
        array $errors,
        array $warnings,
        int $validCount,
        int $totalCount,
        array $ignoredDescriptors,
        bool $verbose,
        bool $quiet
    ): void {
        if ($quiet && count($errors) === 0) {
            return;
        }

        // Output errors
        if (count($errors) > 0) {
            $this->output("Errors:");
            foreach ($errors as $file => $fileErrors) {
                $this->output("  {$file}:");
                foreach ($fileErrors as $error) {
                    $this->output("    - {$error}");
                }
            }
            $this->output("");
        }

        // Output warnings (only in verbose mode)
        if ($verbose && count($warnings) > 0) {
            $this->output("Warnings:");
            foreach ($warnings as $file => $fileWarnings) {
                $this->output("  {$file}:");
                foreach ($fileWarnings as $warning) {
                    $this->output("    - {$warning}");
                }
            }
            $this->output("");
        }

        // Summary
        if (!$quiet) {
            $errorCount = count($errors);
            $warningCount = count($warnings);
            $ignoredCount = count($ignoredDescriptors);

            $this->output("Summary:");
            $this->output("  Total files: {$totalCount}");
            $this->output("  Valid: {$validCount}");
            $this->output("  With errors: {$errorCount}");
            $this->output("  With warnings: {$warningCount}");
            $this->output("  Ignored: {$ignoredCount}");

            if ($errorCount === 0) {
                $this->output("");
                $this->output("All descriptors are valid.");
            }

            // Show ignored descriptors list
            if ($ignoredCount > 0) {
                $this->output("");
                $this->output("Ignored descriptors ({$ignoredCount}):");
                foreach ($ignoredDescriptors as $descriptor) {
                    $verbs = implode(', ', $descriptor->verbs);
                    $this->output("  - {$descriptor->route} [{$verbs}]");
                }
            }
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
