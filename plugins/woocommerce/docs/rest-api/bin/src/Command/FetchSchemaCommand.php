<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Parser\SchemaParser;

/**
 * Command to fetch REST API schema from a WordPress site.
 */
final class FetchSchemaCommand implements CommandInterface
{
    public function __construct(
        private readonly SchemaParser $schemaParser,
        private readonly string $defaultOutputPath,
    ) {
    }

    public function getName(): string
    {
        return 'fetch-schema';
    }

    public function getDescription(): string
    {
        return 'Fetch REST API schema from a WordPress site';
    }

    public function execute(array $options): int
    {
        $verbose = $options['verbose'] ?? false;
        $quiet = $options['quiet'] ?? false;
        $url = $options['url'] ?? null;
        $authToken = $options['auth'] ?? null;
        $outputPath = $options['output'] ?? $this->defaultOutputPath;
        $filter = $options['filter'] ?? null;
        $reset = $options['reset'] ?? false;
        $incremental = !$reset; // Incremental is now the default

        if ($url === null) {
            $this->error("Error: --url is required for fetch-schema command");
            $this->error("Usage: fetch-schema --url=http://your-wordpress-site.com");
            return 1;
        }

        try {
            if (!$quiet) {
                $this->output("Fetching schema from: {$url}");
                if ($filter !== null) {
                    $this->output("Filter for OPTIONS requests: {$filter}");
                }
                if ($reset) {
                    $this->output("Reset mode: will replace existing schema");
                }
            }

            // Progress callback for inline updates (unless quiet)
            $progressCallback = null;
            if (!$quiet) {
                $progressCallback = function(string $msg, bool $inline = false) {
                    if ($inline) {
                        // Overwrite current line
                        echo "\r\033[K" . $msg;
                    } else {
                        echo $msg . PHP_EOL;
                    }
                };
            }

            $schema = $this->schemaParser->fetchAndSaveSchema(
                $url,
                $outputPath,
                $authToken,
                $progressCallback,
                $filter,
                $incremental
            );

            $stats = $this->schemaParser->getStatistics($schema);

            if (!$quiet) {
                $this->output("");
                $this->output("Schema fetched successfully!");
                $this->output("  Total routes: {$stats['total_routes']}");
                $this->output("  WooCommerce routes: {$stats['wc_routes']}");
                $this->output("  WooCommerce endpoints: {$stats['wc_endpoints']}");
                $this->output("  Output file: {$outputPath}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            if ($verbose) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
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
