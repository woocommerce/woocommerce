<?php

declare(strict_types=1);

/**
 * Main implementation for the REST API Documentation Generator.
 * This file contains PHP 8+ syntax and should only be loaded after version check.
 */

// Set up paths
define('BASE_DIR', dirname(__DIR__));
define('SRC_DIR', __DIR__ . '/src');
define('TEMP_DIR', BASE_DIR . '/temp');
define('DESCRIPTORS_DIR', BASE_DIR . '/endpoint-descriptors');
define('DEFAULT_CATEGORIES_FILE', BASE_DIR . '/default-categories.md');
define('TEMPLATES_DIR', SRC_DIR . '/Template/templates');
define('OUTPUT_DIR', BASE_DIR . '/html');
define('ASSETS_DIR', BASE_DIR . '/assets');

// Simple autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'WooCommerce\\RestApiDocs\\';
    $prefixLength = strlen($prefix);

    if (strncmp($prefix, $class, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLength);
    $file = SRC_DIR . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use WooCommerce\RestApiDocs\Command\CompareCommand;
use WooCommerce\RestApiDocs\Command\FetchSchemaCommand;
use WooCommerce\RestApiDocs\Command\GenerateDescriptorsCommand;
use WooCommerce\RestApiDocs\Command\GenerateSiteCommand;
use WooCommerce\RestApiDocs\Command\ListCommand;
use WooCommerce\RestApiDocs\Command\PurgeIgnoredCommand;
use WooCommerce\RestApiDocs\Command\ValidateCommand;
use WooCommerce\RestApiDocs\Parser\DefaultCategoriesParser;
use WooCommerce\RestApiDocs\Parser\DescriptorParser;
use WooCommerce\RestApiDocs\Parser\RouteRegexTypesParser;
use WooCommerce\RestApiDocs\Parser\SchemaParser;
use WooCommerce\RestApiDocs\Util\RouteFormatter;

define('DEFAULT_SCHEMA_PATH', TEMP_DIR . '/rest-api-schema.json');
define('ROUTE_REGEX_TYPES_FILE', BASE_DIR . '/route-regex-types.md');

/**
 * Parse command line arguments.
 *
 * @param array<string> $argv Command line arguments
 * @return array{command: string|null, options: array<string, mixed>}
 */
function parseArguments(array $argv): array
{
    $command = null;
    $options = [
        'url' => null,
        'auth' => null,
        'output' => null,
        'schema' => null,
        'filter' => null,
        'schema-filter' => null,
        'descriptor-filter' => null,
        'verbose' => false,
        'quiet' => false,
        'dry-run' => false,
        'exclude-incomplete' => false,
        'reset' => false,
        'all' => false,
    ];

    // Options that require a value
    $valueOptions = ['url', 'auth', 'output', 'schema', 'filter', 'schema-filter', 'descriptor-filter'];

    // Skip script name
    array_shift($argv);

    $argc = count($argv);
    for ($i = 0; $i < $argc; $i++) {
        $arg = $argv[$i];

        if (str_starts_with($arg, '--')) {
            // Option
            $arg = substr($arg, 2);

            if (str_contains($arg, '=')) {
                // --option=value syntax
                [$key, $value] = explode('=', $arg, 2);
                $options[$key] = $value;
            } elseif (in_array($arg, $valueOptions, true)) {
                // --option value syntax (for options that require values)
                if ($i + 1 < $argc && !str_starts_with($argv[$i + 1], '--')) {
                    $options[$arg] = $argv[++$i];
                } else {
                    fwrite(STDERR, "Error: Option --{$arg} requires a value.\n");
                    fwrite(STDERR, "Usage: --{$arg}=<value> or --{$arg} <value>\n");
                    exit(1);
                }
            } else {
                // Boolean flag
                $options[$arg] = true;
            }
        } elseif ($command === null) {
            // Command
            $command = $arg;
        }
    }

    return [
        'command' => $command,
        'options' => $options,
    ];
}

/**
 * Show help message.
 */
function showHelp(): void
{
    echo <<<'HELP'
WooCommerce REST API Documentation Generator

Usage:
  php generate-docs.php <command> [options]

Commands:
  fetch-schema         Fetch REST API schema from a WordPress site
                       Fetches main schema and response schemas via OPTIONS
                       Saves to temp/rest-api-schema.json by default

  compare              Compare schema endpoints with existing descriptors
                       Lists missing descriptors and orphaned files

  generate-descriptors Generate missing endpoint descriptor files
                       Creates template files for new endpoints

  validate             Validate all endpoint descriptor files
                       Checks syntax, required fields, and schema matches

  list                 List endpoints from schema with their status
                       Shows markdown table with Route, Schema, Descriptor columns

  purge-ignored        Delete ignored endpoint descriptor files
                       By default only deletes TODO: descriptors
                       Use --all to delete all ignored descriptors

  generate-site        Generate the static documentation website
                       Outputs to html/ directory

  help                 Show this help message

Options:
  --url=<URL>          WordPress site URL (required)
                       Example: --url=http://localhost:8888
                       Commands: fetch-schema

  --auth=<TOKEN>       Authentication token (Bearer) for schema fetch
                       Example: --auth=your-token-here
                       Commands: fetch-schema

  --output=<PATH>      Output file path for schema
                       Defaults to temp/rest-api-schema.json
                       Commands: fetch-schema

  --schema=<PATH>      Input schema file path
                       Defaults to temp/rest-api-schema.json
                       Commands: compare, generate-descriptors, validate, generate-site, list

  --filter=<REGEX>     Filter routes by regex pattern or substring
                       Only process routes matching this pattern
                       Example: --filter="wc/v3/products"
                       Example: --filter="wc/v[34]/"
                       Commands: fetch-schema, compare, generate-descriptors, validate,
                                 purge-ignored, generate-site, list

  --schema-filter=<VAL> Filter by schema status
                       Values: yes (has schema), no (no schema), ? (not fetched),
                               no? (no schema or not fetched)
                       Commands: list

  --descriptor-filter=<VAL> Filter by descriptor status
                       Values: yes (has descriptor), no (missing), ignored,
                               yi (yes or ignored)
                       Commands: list

  --verbose            Show detailed output including progress
                       Commands: all

  --quiet              Suppress non-error output
                       Commands: all

  --dry-run            Show what would be done without making changes
                       Commands: generate-descriptors, purge-ignored

  --exclude-incomplete Skip endpoints without schema data
                       Commands: generate-site

  --all                Delete all ignored descriptors, not just TODO: ones
                       Commands: purge-ignored

  --reset              Replace existing data instead of merging/updating
                       By default, commands operate incrementally
                       Commands: fetch-schema, generate-site

Examples:
  # Fetch schema from WordPress site
  php generate-docs.php fetch-schema --url=http://localhost:8888

  # Fetch schema with authentication and verbose output
  php generate-docs.php fetch-schema --url=http://localhost:8888 --auth=token --verbose

  # Fetch schema but only OPTIONS for v3 products endpoints (merges with existing)
  php generate-docs.php fetch-schema --url=http://localhost:8888 --filter="wc/v3/products"

  # Fetch schema replacing any existing data
  php generate-docs.php fetch-schema --url=http://localhost:8888 --reset

  # Compare schema with descriptors
  php generate-docs.php compare

  # Compare only v3 products endpoints
  php generate-docs.php compare --filter="wc/v3/products"

  # Generate missing descriptors (dry run)
  php generate-docs.php generate-descriptors --dry-run

  # Generate descriptors for v4 endpoints only
  php generate-docs.php generate-descriptors --filter="wc/v4/"

  # Validate all descriptors
  php generate-docs.php validate --verbose

  # Delete TODO: ignored descriptors (dry run)
  php generate-docs.php purge-ignored --dry-run

  # Delete all ignored descriptors
  php generate-docs.php purge-ignored --all

  # List all endpoints with their status
  php generate-docs.php list

  # List endpoints missing descriptors
  php generate-docs.php list --descriptor-filter=no

  # List endpoints with schema but missing descriptors
  php generate-docs.php list --schema-filter=yes --descriptor-filter=no

  # List v3 endpoints where schema was not fetched
  php generate-docs.php list --filter="wc/v3/" --schema-filter=?

  # Generate documentation site (incremental by default)
  php generate-docs.php generate-site

  # Regenerate entire site from scratch
  php generate-docs.php generate-site --reset

Exit Codes:
  0  Success
  1  Error (command failed, schema unavailable, etc.)
  2  Validation errors found

HELP;
}

/**
 * Main entry point.
 */
function main(): int
{
    global $argv;

    $parsed = parseArguments($argv);
    $command = $parsed['command'];
    $options = $parsed['options'];

    // Show help
    if ($command === null || $command === 'help') {
        showHelp();
        return 0;
    }

    // Ensure temp directory exists
    if (!is_dir(TEMP_DIR)) {
        mkdir(TEMP_DIR, 0755, true);
    }

    // Create shared dependencies
    $schemaParser = new SchemaParser(TEMP_DIR);
    $descriptorParser = new DescriptorParser(DESCRIPTORS_DIR);
    $categoriesParser = new DefaultCategoriesParser(DEFAULT_CATEGORIES_FILE);
    $regexTypesParser = new RouteRegexTypesParser(ROUTE_REGEX_TYPES_FILE);

    // Initialize RouteFormatter with regex types parser
    RouteFormatter::setRegexTypesParser($regexTypesParser);

    // Route to command
    return match ($command) {
        'fetch-schema' => (new FetchSchemaCommand(
            $schemaParser,
            DEFAULT_SCHEMA_PATH
        ))->execute($options),

        'compare' => (new CompareCommand($schemaParser, $descriptorParser))->execute($options),

        'generate-descriptors' => (new GenerateDescriptorsCommand(
            $schemaParser,
            $descriptorParser,
            $categoriesParser,
            DESCRIPTORS_DIR
        ))->execute($options),

        'validate' => (new ValidateCommand($schemaParser, $descriptorParser))->execute($options),

        'list' => (new ListCommand($schemaParser, $descriptorParser))->execute($options),

        'purge-ignored' => (new PurgeIgnoredCommand(
            $descriptorParser,
            DESCRIPTORS_DIR
        ))->execute($options),

        'generate-site' => (new GenerateSiteCommand(
            $schemaParser,
            $descriptorParser,
            TEMPLATES_DIR,
            OUTPUT_DIR,
            ASSETS_DIR
        ))->execute($options),

        default => handleUnknownCommand($command),
    };
}

/**
 * Handle unknown command.
 */
function handleUnknownCommand(string $command): int
{
    fwrite(STDERR, "Unknown command: {$command}\n");
    fwrite(STDERR, "Run 'php generate-docs.php help' for usage information.\n");
    return 1;
}

// Run and exit with appropriate code
exit(main());
