<?php
/**
 * Boot-time installation integrity check using a build-time file manifest.
 */

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * FileManifest class.
 *
 * Compares the plugin version from the file header against a build-time manifest
 * of expected files. This detects incomplete plugin updates where WordPress fails
 * to fully replace plugin files (e.g., stale autoloader classmaps, renamed vendor
 * files persisting from a previous version).
 *
 * @since 10.6.0
 */
class FileManifest {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * The name of the option used to store the last verified WooCommerce version.
	 *
	 * @var string
	 */
	private const VERIFIED_VERSION_OPTION = 'woocommerce_verified_installation_version';

	/**
	 * Maximum number of missing files to list in the admin notice details.
	 *
	 * @var int
	 */
	private const MAX_MISSING_FILES_SHOWN = 20;

	/**
	 * Directory prefixes excluded from the manifest because they are
	 * stripped from production builds.
	 *
	 * IMPORTANT: This list and the wildcard patterns in is_excluded_path()
	 * must be kept in sync with .distignore. See the test
	 * test_enumerate_excludes_all_distignore_directories in FileManifestTest
	 * which verifies this automatically.
	 *
	 * @var string[]
	 */
	private const EXCLUDED_PREFIXES = array(
		'bin/',
		'build/',
		'changelog/',
		'client/admin/',
		'client/blocks/',
		'client/legacy/',
		'e2e/',
		'node_modules/',
		'packages/email-editor/tasks/',
		'packages/woocommerce-admin/docs/',
		'packages/woocommerce-blocks/docs/',
		'patches/',
		'php-stubs/',
		'playwright-report/',
		'storybook/',
		'test-results/',
		'tests/',
	);

	/**
	 * Enumerate all PHP files in a plugin directory for the manifest.
	 *
	 * Returns a sorted array of relative paths, excluding the manifest file
	 * itself and directories that are stripped from production builds.
	 *
	 * This method has no WordPress dependencies so it can be used by both
	 * the WP-CLI command and the standalone build script.
	 *
	 * @param string $plugin_dir Absolute path to the plugin root directory.
	 * @return string[] Sorted list of relative PHP file paths.
	 */
	public static function enumerate_php_files( $plugin_dir ) {
		$files    = array();
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $plugin_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}

			$relative = substr( $file->getPathname(), strlen( $plugin_dir ) + 1 );

			if ( 'file-manifest.php' === $relative || self::is_excluded_path( $relative ) ) {
				continue;
			}

			$files[] = $relative;
		}

		sort( $files );

		return $files;
	}

	/**
	 * Check whether a relative file path falls within a directory that is
	 * excluded from production builds.
	 *
	 * IMPORTANT: The patterns here must be kept in sync with .distignore
	 * (see also the EXCLUDED_PREFIXES constant).
	 *
	 * @param string $relative_path Relative path from the plugin root.
	 * @return bool True if the path should be excluded from the manifest.
	 */
	private static function is_excluded_path( $relative_path ) {
		foreach ( self::EXCLUDED_PREFIXES as $prefix ) {
			if ( 0 === strpos( $relative_path, $prefix ) ) {
				return true;
			}
		}

		// packages/*/vendor/, packages/*/tests/, packages/*/changelog/, packages/*/node_modules/.
		if ( preg_match( '#^packages/[^/]+/(vendor|tests|changelog|node_modules)(/|$)#', $relative_path ) ) {
			return true;
		}

		// vendor/*/*/tests/.
		if ( preg_match( '#^vendor/[^/]+/[^/]+/tests(/|$)#', $relative_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Generate the manifest file content string.
	 *
	 * This method has no WordPress dependencies so it can be used by both
	 * the WP-CLI command and the standalone build script.
	 *
	 * @param string   $version The plugin version.
	 * @param string[] $files   Sorted list of relative PHP file paths.
	 * @return string The PHP source code for file-manifest.php.
	 */
	public static function generate_manifest_content( $version, $files ) {
		$output  = "<?php\n";
		$output .= "// This file is auto-generated. Do not edit.\n";
		$output .= "return array(\n";
		$output .= "\t'version' => " . var_export( $version, true ) . ",\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$output .= "\t'files'   => array(\n";

		foreach ( $files as $file ) {
			$output .= "\t\t" . var_export( $file, true ) . ",\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		}

		$output .= "\t),\n";
		$output .= ");\n";

		return $output;
	}

	/**
	 * Verify that the WooCommerce installation is complete and consistent.
	 *
	 * The full file check runs only once after each version change and caches the result
	 * in a WordPress option to avoid repeated filesystem scans.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 * @return boolean True if the installation is verified (or if verification is not possible), false if corrupt.
	 */
	public static function verify_installation( $plugin_file ) {
		/**
		 * Filters whether the boot-time file manifest verification is enabled.
		 *
		 * Returning false skips the integrity check entirely, allowing WooCommerce
		 * to load even if the installation appears incomplete.
		 *
		 * @since 10.6.0
		 *
		 * @param bool $enabled Whether verification is enabled. Default true.
		 */
		if ( ! apply_filters( 'woocommerce_file_manifest_verification_enabled', true ) ) {
			return true;
		}

		$plugin_dir    = dirname( $plugin_file );
		$manifest_file = $plugin_dir . '/file-manifest.php';

		if ( ! is_readable( $manifest_file ) ) {
			// No manifest is expected in development environments. In production
			// or staging it likely means the build artifact was stripped or the
			// update was incomplete — warn but don't block WooCommerce.
			$env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
			if ( ! in_array( $env, array( 'local', 'development' ), true ) ) {
				self::missing_manifest_warning();
			}
			return true;
		}

		$plugin_data    = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
		$plugin_version = $plugin_data['Version'] ?? '';

		if ( empty( $plugin_version ) ) {
			return true;
		}

		// Skip the full file check if this version was already verified.
		$verified_version = get_option( self::VERIFIED_VERSION_OPTION );
		if ( $plugin_version === $verified_version ) {
			return true;
		}

		// Load the manifest in an isolated scope so that any variables defined
		// inside the manifest file don't leak into this method's scope.
		$manifest = ( static function ( $file ) {
			return require $file;
		} )( $manifest_file );

		if ( ! is_array( $manifest ) || ! isset( $manifest['version'], $manifest['files'] ) ) {
			return true;
		}

		// Compare versions: strip pre-release suffixes so that version_compare
		// can handle the Composer 4-component normalization (10.5.0 == 10.5.0.0).
		$strip_suffix     = static function ( $version ) {
			return preg_replace( '/-.*$/', '', $version );
		};
		$manifest_version = $strip_suffix( $manifest['version'] );
		$expected_version = $strip_suffix( $plugin_version );

		if ( 0 !== version_compare( $manifest_version, $expected_version ) ) {
			self::incomplete_installation(
				$plugin_file,
				array(
					sprintf( 'Expected version: %s', $plugin_version ),
					sprintf( 'Manifest version: %s', $manifest['version'] ),
				),
				__( 'Version mismatch detected:', 'woocommerce' )
			);
			return false;
		}

		// Verify that every file listed in the manifest actually exists on disk.
		$missing_files = array();
		foreach ( $manifest['files'] as $relative_path ) {
			if ( ! file_exists( $plugin_dir . '/' . $relative_path ) ) {
				$missing_files[] = $relative_path;
			}
		}

		if ( ! empty( $missing_files ) ) {
			$details = array_slice( $missing_files, 0, self::MAX_MISSING_FILES_SHOWN );
			if ( count( $missing_files ) > self::MAX_MISSING_FILES_SHOWN ) {
				$details[] = sprintf( '...and %d more', count( $missing_files ) - self::MAX_MISSING_FILES_SHOWN );
			}
			self::incomplete_installation(
				$plugin_file,
				$details,
				__( 'The following files are missing from the WooCommerce installation:', 'woocommerce' )
			);
			return false;
		}

		// All checks passed — record the verified version.
		update_option( self::VERIFIED_VERSION_OPTION, $plugin_version, true );

		return true;
	}

	/**
	 * If the installation is incomplete or corrupted, add an admin notice and
	 * hide WooCommerce from the active plugins list for the current request.
	 *
	 * @since 10.6.0
	 * 
	 * @param string   $plugin_file    Absolute path to the main plugin file (woocommerce.php).
	 * @param string[] $details         Optional list of detail lines (e.g. missing file paths) to show in an expandable section.
	 * @param string   $details_heading Optional heading displayed above the details list (already translated).
	 */
	public static function incomplete_installation( $plugin_file, $details = array(), $details_heading = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				'WooCommerce installation appears to be incomplete or corrupted. '
				. 'The plugin files do not match the expected version. '
				. 'Please deactivate WooCommerce and install it again.'
			);
		}
		add_action(
			'admin_notices',
			function () use ( $details, $details_heading ) {
				?>
				<div class="notice notice-error" style="border-left-width: 4px; padding: 12px 16px;">
					<p style="font-size: 14px; font-weight: 600; margin: 4px 0;">
						<?php
						esc_html_e(
							'WooCommerce has been disabled because the installation appears to be incomplete or corrupted.',
							'woocommerce'
						);
						?>
					</p>
					<p style="margin: 8px 0 4px;">
						<?php
						esc_html_e(
							'This can happen when a plugin update does not fully complete. Please deactivate WooCommerce and install it again.',
							'woocommerce'
						);
						?>
					</p>
					<?php if ( ! empty( $details ) ) : ?>
					<details style="margin-top: 8px;">
						<summary style="cursor: pointer; color: #787c82;">
							<?php esc_html_e( 'Show details', 'woocommerce' ); ?>
						</summary>
						<?php if ( ! empty( $details_heading ) ) : ?>
						<p style="margin: 8px 0 4px;">
							<?php echo esc_html( $details_heading ); ?>
						</p>
						<?php endif; ?>
						<ul style="margin: 4px 0 0; padding-left: 20px;">
							<?php foreach ( $details as $detail ) : ?>
								<li>
								<?php if ( str_starts_with( $detail, '...' ) ) : ?>
									<?php echo esc_html( $detail ); ?>
								<?php else : ?>
									<code><?php echo esc_html( $detail ); ?></code>
								<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</details>
					<?php endif; ?>
				</div>
				<?php
			}
		);

		self::hide_from_active_plugins( $plugin_file );
		self::register_cli_diagnostics();
	}

	/**
	 * Filter WooCommerce out of the active plugins list for the current request.
	 *
	 * This makes is_plugin_active() return false so that dependent plugins
	 * see WooCommerce as inactive and skip their integrations instead of
	 * crashing. Only the in-memory option value is affected — WooCommerce
	 * remains active in the database and will be loaded again on the next request.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 */
	private static function hide_from_active_plugins( $plugin_file ) {
		$wc_basename = plugin_basename( $plugin_file );

		// Single-site (or per-site in multisite): flat array of plugin basenames.
		add_filter(
			'option_active_plugins',
			static function ( $plugins ) use ( $wc_basename ) {
				return array_values(
					array_filter(
						(array) $plugins,
						static function ( $plugin ) use ( $wc_basename ) {
							return $plugin !== $wc_basename;
						}
					)
				);
			}
		);

		// Network-wide activation in multisite: associative array keyed by basename.
		add_filter(
			'site_option_active_sitewide_plugins',
			static function ( $plugins ) use ( $wc_basename ) {
				unset( $plugins[ $wc_basename ] );
				return $plugins;
			}
		);
	}

	/**
	 * Show a warning when the file manifest is missing in a non-development environment.
	 *
	 * WooCommerce still loads normally, but the admin is informed that the
	 * integrity check could not run.
	 */
	private static function missing_manifest_warning() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WooCommerce file manifest (file-manifest.php) is missing. Installation integrity cannot be verified.' );  // phpcs:ignore
		}
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-warning">
					<p>
						<?php
						esc_html_e(
							'The WooCommerce installation integrity could not be verified because the file manifest is missing. This may indicate an incomplete update.',
							'woocommerce'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Register the manifest CLI commands even when WooCommerce is disabled,
	 * so the user can run "wp wc manifest verify" to diagnose the issue.
	 */
	private static function register_cli_diagnostics() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		// The autoloader is not available at this point, so the file is loaded manually.
		require_once __DIR__ . '/CLI/FileManifestCommand.php';
		$command = new \Automattic\WooCommerce\Internal\CLI\FileManifestCommand();
		$command->register_commands();
	}
}
