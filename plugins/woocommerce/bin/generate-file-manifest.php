#!/usr/bin/env php
<?php
/**
 * Generates a file manifest for installation integrity verification.
 *
 * This script enumerates all PHP files in the WooCommerce plugin directory
 * and writes a manifest file that the runtime integrity check uses to detect
 * incomplete plugin updates.
 *
 * Usage: php generate-file-manifest.php [plugin-directory]
 *   If no directory is specified, the parent directory of bin/ is used.
 *
 * @package WooCommerce
 */

// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.Security.EscapeOutput

$plugin_dir = $argv[1] ?? dirname( __DIR__ );
$plugin_dir = rtrim( realpath( $plugin_dir ), '/' );

if ( ! is_dir( $plugin_dir ) ) {
	fwrite( STDERR, "Error: Plugin directory not found: $plugin_dir\n" );
	exit( 1 );
}

$plugin_file = $plugin_dir . '/woocommerce.php';
if ( ! is_file( $plugin_file ) ) {
	fwrite( STDERR, "Error: woocommerce.php not found in: $plugin_dir\n" );
	exit( 1 );
}

// Read version from plugin header.
$header  = file_get_contents( $plugin_file, false, null, 0, 8192 );
$version = null;
if ( preg_match( '/^\s*\*?\s*Version:\s*(.+)$/mi', $header, $matches ) ) {
	$version = trim( $matches[1] );
}

if ( empty( $version ) ) {
	fwrite( STDERR, "Error: Could not read version from plugin header.\n" );
	exit( 1 );
}

// Allow requiring FileManifest.php outside of WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/' );
}

require_once $plugin_dir . '/src/Internal/FileManifest.php';

use Automattic\WooCommerce\Internal\FileManifest;

$files  = FileManifest::enumerate_php_files( $plugin_dir );
$output = FileManifest::generate_manifest_content( $version, $files );

$manifest_path = $plugin_dir . '/file-manifest.php';
file_put_contents( $manifest_path, $output );

$count = count( $files );
echo "Generated file-manifest.php: version=$version, files=$count\n";
