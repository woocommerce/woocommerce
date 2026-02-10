#!/usr/bin/env php
<?php
/**
 * Generates a file manifest for installation integrity verification.
 *
 * This script enumerates all PHP files in a WooCommerce plugin directory
 * and writes a manifest file that the runtime integrity check uses to detect
 * incomplete plugin updates.
 *
 * Usage: php generate-file-manifest.php [plugin-directory]
 *
 *   With a directory argument the manifest is generated directly in that
 *   directory. This is the mode used by build-zip.sh where the directory
 *   has already been filtered by .distignore.
 *
 *   Without arguments the script creates a temporary copy of the plugin
 *   tree filtered through .distignore, generates the manifest there, and
 *   copies it back to the plugin root. This is the mode used during local
 *   development via "pnpm run build:manifest".
 *
 * @package WooCommerce
 */

// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.Security.EscapeOutput

$source_dir = rtrim( realpath( dirname( __DIR__ ) ), '/' );

if ( isset( $argv[1] ) ) {
	// Direct mode: generate in the given (already clean) directory.
	$target_dir = rtrim( realpath( $argv[1] ), '/' );
	$cleanup    = null;
} else {
	// Local dev mode: rsync to a temp directory using .distignore.
	$distignore = $source_dir . '/.distignore';
	if ( ! is_file( $distignore ) ) {
		fwrite( STDERR, "Error: .distignore not found in: $source_dir\n" );
		exit( 1 );
	}

	$target_dir = sys_get_temp_dir() . '/wc-manifest-build-' . getmypid();
	mkdir( $target_dir, 0755, true );
	$cleanup = static function () use ( $target_dir ) {
		exec( 'rm -rf ' . escapeshellarg( $target_dir ) );
	};
	register_shutdown_function( $cleanup );

	echo "Syncing production files...\n";
	$rsync_cmd = sprintf(
		'rsync -rc --exclude-from=%s %s/ %s/ --delete --delete-excluded',
		escapeshellarg( $distignore ),
		escapeshellarg( $source_dir ),
		escapeshellarg( $target_dir )
	);
	exec( $rsync_cmd, $rsync_output, $rsync_exit );
	if ( 0 !== $rsync_exit ) {
		fwrite( STDERR, "Error: rsync failed with exit code $rsync_exit\n" );
		exit( 1 );
	}

	// Strip dev-only PHP packages so the manifest matches a production build.
	// We query the source directory's composer (where path repos resolve correctly)
	// and delete the dev-only packages from the temp directory.
	echo "Removing dev dependencies...\n";
	$all_packages  = array();
	$prod_packages = array();
	exec( sprintf( 'XDEBUG_MODE=off composer show --name-only --working-dir=%s 2>/dev/null', escapeshellarg( $source_dir ) ), $all_packages );
	exec( sprintf( 'XDEBUG_MODE=off composer show --no-dev --name-only --working-dir=%s 2>/dev/null', escapeshellarg( $source_dir ) ), $prod_packages );
	$dev_packages = array_diff( $all_packages, $prod_packages );
	foreach ( $dev_packages as $package ) {
		$package_dir = $target_dir . '/vendor/' . trim( $package );
		if ( is_dir( $package_dir ) ) {
			exec( 'rm -rf ' . escapeshellarg( $package_dir ) );
		}
	}
}

if ( ! is_dir( $target_dir ) ) {
	fwrite( STDERR, "Error: Plugin directory not found: $target_dir\n" );
	exit( 1 );
}

$plugin_file = $target_dir . '/woocommerce.php';
if ( ! is_file( $plugin_file ) ) {
	fwrite( STDERR, "Error: woocommerce.php not found in: $target_dir\n" );
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

require_once $target_dir . '/src/Internal/FileManifest.php';

use Automattic\WooCommerce\Internal\FileManifest;

$files  = FileManifest::enumerate_php_files( $target_dir );
$output = FileManifest::generate_manifest_content( $version, $files );

$manifest_path = $target_dir . '/file-manifest.php';
file_put_contents( $manifest_path, $output );

$count = count( $files );
echo "Generated file-manifest.php: version=$version, files=$count\n";

// In local dev mode, copy the manifest back to the source tree.
if ( ! is_null( $cleanup ) ) {
	copy( $manifest_path, $source_dir . '/file-manifest.php' );
	echo "Copied file-manifest.php to plugin root.\n";
}
