#!/usr/bin/env php
<?php
/**
 * WooCommerce REST API Documentation Generator
 *
 * A CLI tool for generating REST API documentation from WordPress schema
 * and endpoint descriptor files.
 *
 * Usage:
 *   php generate-docs.php <command> [options]
 *
 * Commands:
 *   compare              Compare schema endpoints with existing descriptors
 *   generate-descriptors Generate missing endpoint descriptor files
 *   validate             Validate all endpoint descriptor files
 *   help                 Show this help message
 *
 * Options:
 *   --url=<URL>          WordPress site URL to fetch schema from
 *   --auth=<TOKEN>       Authentication token (Bearer)
 *   --verbose            Show detailed output
 *   --quiet              Suppress non-error output
 *   --dry-run            Don't make changes (for generate-descriptors)
 */

// Ensure we're running from CLI
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Check PHP version BEFORE loading any PHP 8+ code
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "This script requires PHP 8.1.0 or higher. You are running " . PHP_VERSION . "\n";
    exit(1);
}

// PHP version is OK, load the main implementation
require_once __DIR__ . '/main.php';
