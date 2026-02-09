<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI;

use Automattic\WooCommerce\Internal\FileManifest;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI commands for managing the file manifest used by the installation integrity check.
 *
 * @since 10.6.0
 */
class FileManifestCommand {

	/**
	 * Register the CLI commands.
	 */
	public function register_commands(): void {
		WP_CLI::add_command( 'wc file-manifest generate', array( $this, 'generate' ) );
		WP_CLI::add_command( 'wc file-manifest verify', array( $this, 'verify' ) );
		WP_CLI::add_command( 'wc file-manifest delete', array( $this, 'delete' ) );
		WP_CLI::add_command( 'wc file-manifest recheck', array( $this, 'recheck' ) );
	}
	
	/**
	 * Generate the file manifest from the current plugin files.
	 *
	 * Enumerates all .php files in the WooCommerce plugin directory
	 * and writes a file-manifest.php that the boot-time integrity check
	 * uses to detect incomplete plugin updates.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc file-manifest generate
	 *     Generated file-manifest.php: version=10.6.0-dev, files=8232
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function generate( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );

		$plugin_dir  = dirname( WC_PLUGIN_FILE );
		$plugin_data = get_file_data( WC_PLUGIN_FILE, array( 'Version' => 'Version' ) );
		$version     = $plugin_data['Version'] ?? '';

		if ( empty( $version ) ) {
			WP_CLI::error( 'Could not read version from the plugin header.' );
		}

		$files         = FileManifest::enumerate_php_files( $plugin_dir );
		$output        = FileManifest::generate_manifest_content( $version, $files );
		$manifest_path = $plugin_dir . '/file-manifest.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $manifest_path, $output ) ) {
			WP_CLI::error( 'Failed to write file-manifest.php.' );
		}

		WP_CLI::success( sprintf( 'Generated %s: version=%s, files=%d', $manifest_path, $version, count( $files ) ) );
	}

	/**
	 * Verify the installation integrity against the file manifest.
	 *
	 * Checks that the manifest version matches the plugin version and
	 * that every file listed in the manifest exists on disk. Reports
	 * any mismatches or missing files.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc file-manifest verify
	 *     Plugin version: 10.6.0-dev
	 *     Manifest version: 10.6.0-dev
	 *     Checking 8232 files...
	 *     Success: All files present. Installation is consistent.
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function verify( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );

		$plugin_dir    = dirname( WC_PLUGIN_FILE );
		$manifest_file = $plugin_dir . '/file-manifest.php';

		if ( ! is_readable( $manifest_file ) ) {
			WP_CLI::error( 'No file-manifest.php found. Run "wp wc file-manifest generate" first.' );
		}

		$plugin_data    = get_file_data( WC_PLUGIN_FILE, array( 'Version' => 'Version' ) );
		$plugin_version = $plugin_data['Version'] ?? '';

		// Load the manifest in an isolated scope.
		$manifest = ( static function ( $file ) {
			return require $file;
		} )( $manifest_file );

		if ( ! is_array( $manifest ) || ! isset( $manifest['version'], $manifest['files'] ) ) {
			WP_CLI::error( 'Manifest file has an invalid structure.' );
		}

		WP_CLI::log( sprintf( 'Plugin version:   %s', $plugin_version ) );
		WP_CLI::log( sprintf( 'Manifest version: %s', $manifest['version'] ) );

		$strip_suffix = static function ( $version ) {
			return preg_replace( '/-.*$/', '', $version );
		};

		if ( 0 !== version_compare( $strip_suffix( $manifest['version'] ), $strip_suffix( $plugin_version ) ) ) {
			WP_CLI::error( 'Version mismatch: manifest version does not match the plugin version.' );
		}

		$file_count    = count( $manifest['files'] );
		$missing_files = array();

		WP_CLI::log( sprintf( 'Checking %d files...', $file_count ) );

		foreach ( $manifest['files'] as $relative_path ) {
			if ( ! file_exists( $plugin_dir . '/' . $relative_path ) ) {
				$missing_files[] = $relative_path;
			}
		}

		if ( ! empty( $missing_files ) ) {
			WP_CLI::warning( sprintf( '%d missing file(s):', count( $missing_files ) ) );
			foreach ( $missing_files as $missing ) {
				WP_CLI::log( '  - ' . $missing );
			}
			WP_CLI::error( 'Installation is inconsistent. Missing files detected.' );
		}

		$verified_version = get_option( 'woocommerce_verified_installation_version' );
		WP_CLI::log( sprintf( 'Cached verified version: %s', $verified_version ? $verified_version : '(none)' ) );

		WP_CLI::success( 'All files present. Installation is consistent.' );
	}

	/**
	 * Delete the file manifest.
	 *
	 * Removes the file-manifest.php file and clears the cached
	 * verified version option, so the next request will skip
	 * the integrity check (as if running in a development environment).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc file-manifest delete
	 *     Success: file-manifest.php deleted and verified version cache cleared.
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function delete( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );

		$manifest_path = dirname( WC_PLUGIN_FILE ) . '/file-manifest.php';

		if ( file_exists( $manifest_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			if ( ! unlink( $manifest_path ) ) {
				WP_CLI::error( 'Failed to delete file-manifest.php.' );
			}
			WP_CLI::log( 'Deleted file-manifest.php.' );
		} else {
			WP_CLI::log( 'No file-manifest.php found (already absent).' );
		}

		delete_option( 'woocommerce_verified_installation_version' );
		WP_CLI::success( 'Verified version cache cleared.' );
	}

	/**
	 * Force the integrity check to run again on the next page load.
	 *
	 * Clears the cached verified version so the boot-time check
	 * re-validates the manifest on the next request.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc file-manifest recheck
	 *     Success: Integrity check will run on the next page load.
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 */
	public function recheck( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );

		delete_option( 'woocommerce_verified_installation_version' );
		WP_CLI::success( 'Integrity check will run on the next page load.' );
	}
}
