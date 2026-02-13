<?php
/**
 * Boot-time installation integrity check using a build-time file manifest.
 */

declare( strict_types=1 );

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
	 * The name of the option used to store the file manifest check result.
	 *
	 * Holds a structured array with version, date, status, and details.
	 * Used as the single skip guard (replaces the former separate verified-version option)
	 * and as the data source for the WooCommerce status report section.
	 *
	 * @var string
	 */
	private const CHECK_RESULT_OPTION = 'woocommerce_file_manifest_check_result';

	/**
	 * Maximum number of missing files to list in the admin notice details.
	 *
	 * @var int
	 */
	private const MAX_MISSING_FILES_SHOWN = 20;

	/**
	 * Return the stored file manifest check result.
	 *
	 * Encapsulates the option name so callers (e.g. the REST system status
	 * controller) don't need to know the internal storage key.
	 *
	 * @since 10.6.0
	 *
	 * @return array|null The stored result array, or null if not available.
	 */
	public static function get_check_result(): ?array {
		$result = get_option( self::CHECK_RESULT_OPTION );
		return is_array( $result ) ? $result : null;
	}

	/**
	 * Run a fresh file manifest verification without storing the result.
	 *
	 * Thin public wrapper around the private run_verification() method,
	 * intended for on-demand checks via the REST API or WP-CLI.
	 *
	 * @since 10.6.0
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 * @return array{status: string, details: string[], version: string, manifest_version?: string}
	 */
	public static function run_fresh_verification( string $plugin_file ): array {
		return self::run_verification( $plugin_file );
	}

	/**
	 * Store a fresh verification result in the database.
	 *
	 * Replaces any previously cached check result. Intended for use
	 * alongside run_fresh_verification() when the caller wants to
	 * persist the result (e.g. the --store-result CLI flag).
	 *
	 * @since 10.6.0
	 *
	 * @param array $result The result array from run_fresh_verification().
	 */
	public static function store_fresh_result( array $result ): void {
		delete_option( self::CHECK_RESULT_OPTION );
		self::store_result( $result );
	}

	/**
	 * Enumerate all PHP files in a directory for the manifest.
	 *
	 * Returns a sorted array of relative paths, excluding only the manifest
	 * file itself. The directory should contain only production files (e.g.
	 * a pre-built plugin directory filtered by .distignore).
	 *
	 * This method has no WordPress dependencies so it can be used by both
	 * the WP-CLI command and the standalone build script.
	 *
	 * @param string $plugin_dir Absolute path to the directory to scan.
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

			if ( 'file-manifest.php' === $relative ) {
				continue;
			}

			$files[] = $relative;
		}

		sort( $files );

		return $files;
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
	 * Run the core file manifest verification logic.
	 *
	 * Reads the manifest file, compares versions, and checks that all listed
	 * files exist on disk. Does not interact with WordPress options or admin notices.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 * @return array{status: string, details: string[], version: string, manifest_version?: string}
	 *   - status: 'pass', 'version_mismatch', 'missing_files', 'no_manifest', 'skipped'
	 *   - details: array of detail strings (e.g. missing file paths)
	 *   - version: the plugin version checked
	 *   - manifest_version: (version_mismatch only) the version found in the manifest
	 */
	private static function run_verification( $plugin_file ): array {
		$plugin_dir    = dirname( $plugin_file );
		$manifest_file = $plugin_dir . '/file-manifest.php';

		$plugin_data    = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
		$plugin_version = $plugin_data['Version'] ?? '';

		if ( ! is_readable( $manifest_file ) ) {
			return array(
				'status'  => 'no_manifest',
				'details' => array(),
				'version' => $plugin_version,
			);
		}

		if ( empty( $plugin_version ) ) {
			return array(
				'status'  => 'skipped',
				'details' => array( 'Could not read plugin version.' ),
				'version' => '',
			);
		}

		// Load the manifest in an isolated scope so that any variables defined
		// inside the manifest file don't leak into this method's scope.
		$manifest = ( static function ( $file ) {
			return require $file;
		} )( $manifest_file );

		if ( ! is_array( $manifest ) || ! isset( $manifest['version'], $manifest['files'] ) ) {
			return array(
				'status'  => 'skipped',
				'details' => array( 'Manifest file has an invalid structure.' ),
				'version' => $plugin_version,
			);
		}

		// Compare versions: strip pre-release suffixes so that version_compare
		// can handle the Composer 4-component normalization (10.5.0 == 10.5.0.0).
		$strip_suffix     = static function ( $version ) {
			return preg_replace( '/-.*$/', '', $version );
		};
		$manifest_version = $strip_suffix( $manifest['version'] );
		$expected_version = $strip_suffix( $plugin_version );

		if ( 0 !== version_compare( $manifest_version, $expected_version ) ) {
			return array(
				'status'           => 'version_mismatch',
				'details'          => array(
					sprintf( 'Manifest version: %s', $manifest['version'] ),
				),
				'version'          => $plugin_version,
				'manifest_version' => $manifest['version'],
			);
		}

		// Verify that every file listed in the manifest actually exists on disk.
		$missing_files = array();
		foreach ( $manifest['files'] as $relative_path ) {
			if ( ! file_exists( $plugin_dir . '/' . $relative_path ) ) {
				$missing_files[] = $relative_path;
			}
		}

		if ( ! empty( $missing_files ) ) {
			return array(
				'status'  => 'missing_files',
				'details' => $missing_files,
				'version' => $plugin_version,
			);
		}

		return array(
			'status'  => 'pass',
			'details' => array(),
			'version' => $plugin_version,
		);
	}

	/**
	 * Read the stored check result and return it if the version matches.
	 *
	 * @param string $plugin_version The current plugin version to match against.
	 * @return array|null The stored result array, or null if not found or version differs.
	 */
	private static function get_stored_result_for_version( $plugin_version ): ?array {
		$stored = get_option( self::CHECK_RESULT_OPTION );

		return is_array( $stored ) && ( $stored['version'] ?? '' ) === $plugin_version ? $stored : null;
	}

	/**
	 * Store the verification result as a WordPress option.
	 *
	 * @param array $result The result from run_verification().
	 */
	private static function store_result( $result ): void {
		$stored_result = array(
			'version' => $result['version'],
			'date'    => gmdate( 'Y-m-d H:i:s' ),
			'status'  => $result['status'],
			'details' => $result['details'],
		);

		if ( isset( $result['manifest_version'] ) ) {
			$stored_result['manifest_version'] = $result['manifest_version'];
		}

		update_option( self::CHECK_RESULT_OPTION, $stored_result, true );
	}

	/**
	 * Verify that the WooCommerce installation is complete and consistent.
	 *
	 * The behavior depends on the WC_DISABLE_ON_INTEGRITY_CHECK_FAILURE constant:
	 *
	 * - When undefined or false (default): runs verification once per version change, stores
	 *   the result in a WordPress option for display in the status report, and always returns true.
	 * - When true: runs verification on every request until it passes. On failure, disables
	 *   WooCommerce and shows an admin notice. On success, caches the result.
	 *
	 * The constant must be defined before WooCommerce loads (e.g. in wp-config.php).
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 * @return boolean True if the installation is verified (or if verification is not possible), false if corrupt.
	 */
	public static function verify_installation( $plugin_file ) {
		return self::is_disabling_mode()
			? self::verify_disabling_mode( $plugin_file )
			: self::verify_non_disabling_mode( $plugin_file );
	}

	/**
	 * Override for disabling mode, used by unit tests via reflection.
	 * When non-null, takes precedence over the constant.
	 *
	 * @var bool|null
	 */
	private static $disabling_mode_override = null;

	/**
	 * Check whether disabling mode is active.
	 *
	 * Disabling mode is enabled by defining the WC_DISABLE_ON_INTEGRITY_CHECK_FAILURE
	 * constant as true (e.g. in wp-config.php) before WooCommerce loads.
	 *
	 * @return bool
	 */
	private static function is_disabling_mode(): bool {
		if ( null !== self::$disabling_mode_override ) {
			return self::$disabling_mode_override;
		}
		return defined( 'WC_DISABLE_ON_INTEGRITY_CHECK_FAILURE' ) && WC_DISABLE_ON_INTEGRITY_CHECK_FAILURE;
	}

	/**
	 * Disabling mode: trust any stored result; run fresh only when the
	 * option has been deleted (recheck tool, CLI, REST) or the version
	 * changes. On failure, disables WooCommerce without storing, so
	 * the next request re-checks. On success, caches the result.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 * @return boolean True if verification passes, false if the installation is corrupt.
	 */
	private static function verify_disabling_mode( $plugin_file ): bool {
		$plugin_data    = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
		$plugin_version = $plugin_data['Version'] ?? '';

		// Trust any stored result for this version. Disabling mode only
		// runs a fresh check when the option has been deleted (recheck
		// tool, CLI reset, or REST endpoint) or when the version changes.
		// Failures are never stored, so a missing option after a failed
		// check causes re-verification on the next request.
		if ( ! empty( $plugin_version ) ) {
			$stored = self::get_stored_result_for_version( $plugin_version );
			if ( ! is_null( $stored ) ) {
				if ( 'no_manifest' === $stored['status'] ) {
					self::maybe_show_missing_manifest_warning();
				}
				add_action( 'woocommerce_system_status_report', array( static::class, 'handle_woocommerce_system_status_report' ) );
				add_filter( 'woocommerce_debug_tools', array( static::class, 'handle_woocommerce_debug_tools' ) );
				return true;
			}
		}

		$result = self::run_verification( $plugin_file );

		if ( 'no_manifest' === $result['status'] ) {
			self::maybe_show_missing_manifest_warning();
			self::store_result( $result );
			add_action( 'woocommerce_system_status_report', array( static::class, 'handle_woocommerce_system_status_report' ) );
			add_filter( 'woocommerce_debug_tools', array( static::class, 'handle_woocommerce_debug_tools' ) );
			return true;
		}

		if ( 'skipped' === $result['status'] ) {
			add_action( 'woocommerce_system_status_report', array( static::class, 'handle_woocommerce_system_status_report' ) );
			add_filter( 'woocommerce_debug_tools', array( static::class, 'handle_woocommerce_debug_tools' ) );
			return true;
		}

		if ( 'version_mismatch' === $result['status'] ) {
			$details   = $result['details'];
			$details[] = sprintf( 'Expected version: %s', $result['version'] );
			self::incomplete_installation(
				$plugin_file,
				$details,
				__( 'Version mismatch detected:', 'woocommerce' )
			);
			return false;
		}

		if ( 'missing_files' === $result['status'] ) {
			$details = array_slice( $result['details'], 0, self::MAX_MISSING_FILES_SHOWN );
			if ( count( $result['details'] ) > self::MAX_MISSING_FILES_SHOWN ) {
				$details[] = sprintf( '...and %d more', count( $result['details'] ) - self::MAX_MISSING_FILES_SHOWN );
			}
			self::incomplete_installation(
				$plugin_file,
				$details,
				__( 'The following files are missing from the WooCommerce installation:', 'woocommerce' )
			);
			return false;
		}

		// All checks passed — cache the result.
		self::store_result( $result );

		add_action( 'woocommerce_system_status_report', array( static::class, 'handle_woocommerce_system_status_report' ) );
		add_filter( 'woocommerce_debug_tools', array( static::class, 'handle_woocommerce_debug_tools' ) );

		return true;
	}

	/**
	 * Non-disabling mode: run verification once per version change and store the result.
	 *
	 * Always returns true so WooCommerce loads normally. The result is stored
	 * in a WordPress option for display in the status report.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (woocommerce.php).
	 * @return boolean Always true.
	 */
	private static function verify_non_disabling_mode( $plugin_file ): bool {
		$plugin_data    = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
		$plugin_version = $plugin_data['Version'] ?? '';

		// Skip if we already have a stored result for this version.
		// Changes to the manifest file (appearing or disappearing) require
		// a manual recheck via WP-CLI or the admin tools page.
		if ( ! empty( $plugin_version ) ) {
			$stored = self::get_stored_result_for_version( $plugin_version );
			if ( ! is_null( $stored ) ) {
				if ( 'no_manifest' === $stored['status'] ) {
					self::maybe_show_missing_manifest_warning();
				}
				add_action( 'woocommerce_system_status_report', array( static::class, 'handle_woocommerce_system_status_report' ) );
				add_filter( 'woocommerce_debug_tools', array( static::class, 'handle_woocommerce_debug_tools' ) );
				return true;
			}
		}

		$result = self::run_verification( $plugin_file );

		// Store the structured result for the status report.
		if ( 'skipped' !== $result['status'] ) {
			self::store_result( $result );
		}

		if ( 'no_manifest' === $result['status'] ) {
			self::maybe_show_missing_manifest_warning();
		}

		add_action( 'woocommerce_system_status_report', array( static::class, 'handle_woocommerce_system_status_report' ) );
		add_filter( 'woocommerce_debug_tools', array( static::class, 'handle_woocommerce_debug_tools' ) );

		return true;
	}

	/**
	 * Render the installation integrity section in the WooCommerce status report.
	 *
	 * Reads the stored check result from the database and displays it in a
	 * status table following the existing WooCommerce status report pattern.
	 *
	 * @internal For exclusive use of WooCommerce, backwards compatibility not guaranteed.
	 * @since 10.6.0
	 */
	public static function handle_woocommerce_system_status_report(): void {
		$result = get_option( self::CHECK_RESULT_OPTION );
		?>
		<table class="wc_status_table widefat" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="Installation integrity">
					<h2>
						<?php esc_html_e( 'Installation integrity', 'woocommerce' ); ?>
						<?php echo wc_help_tip( esc_html__( 'Verifies that the WooCommerce plugin files match the expected version and are complete.', 'woocommerce' ) ); ?>
					</h2>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php if ( ! is_array( $result ) ) : ?>
				<tr>
					<td data-export-label="Status"><?php esc_html_e( 'Status:', 'woocommerce' ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php esc_html_e( 'Not yet verified', 'woocommerce' ); ?></td>
				</tr>
			<?php else : ?>
				<tr>
					<td data-export-label="Status"><?php esc_html_e( 'Status:', 'woocommerce' ); ?></td>
					<td class="help">&nbsp;</td>
					<td>
						<?php if ( 'pass' === $result['status'] ) : ?>
							<mark class="yes"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Pass', 'woocommerce' ); ?></mark>
						<?php elseif ( 'no_manifest' === $result['status'] ) : ?>
							<mark class="no"><?php esc_html_e( 'No manifest available', 'woocommerce' ); ?></mark>
						<?php else : ?>
							<mark class="error"><span class="dashicons dashicons-warning"></span>
							<?php
							if ( 'version_mismatch' === $result['status'] ) {
								esc_html_e( 'Fail - version mismatch', 'woocommerce' );
							} else {
								esc_html_e( 'Fail - missing files', 'woocommerce' );
							}
							?>
							</mark>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td data-export-label="Verified version"><?php esc_html_e( 'Verified version:', 'woocommerce' ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo esc_html( $result['version'] ); ?></td>
				</tr>
				<tr>
					<td data-export-label="Check date"><?php esc_html_e( 'Check date:', 'woocommerce' ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo esc_html( $result['date'] ); ?></td>
				</tr>
				<?php if ( ! empty( $result['details'] ) ) : ?>
				<tr>
					<td data-export-label="Details"><?php esc_html_e( 'Details:', 'woocommerce' ); ?></td>
					<td class="help">&nbsp;</td>
					<?php
					// The value cell is built with echo to control whitespace:
					// jQuery .text() is used by the "copy for support" feature,
					// and template indentation would leak into the copied text.
					$heading = 'version_mismatch' === $result['status']
						? __( 'Version mismatch:', 'woocommerce' )
						: __( 'Missing files:', 'woocommerce' );

					$max_shown = array_slice( $result['details'], 0, self::MAX_MISSING_FILES_SHOWN );
					$overflow  = count( $result['details'] ) > self::MAX_MISSING_FILES_SHOWN;

					echo '<td>' . esc_html( $heading );
					if ( 'version_mismatch' === $result['status'] ) {
						echo "\n<p style=\"margin:2px 0;\">" . esc_html( sprintf( 'Expected version: %s', $result['version'] ) ) . '</p>';
					}
					foreach ( $max_shown as $detail ) {
						echo "\n<p style=\"margin:2px 0;\">" . esc_html( $detail ) . '</p>';
					}
					if ( $overflow ) {
						echo "\n<p style=\"margin:2px 0;\">" . esc_html( sprintf( '...and %d more', count( $result['details'] ) - self::MAX_MISSING_FILES_SHOWN ) ) . '</p>';
					}
					echo '</td>';
					?>
				</tr>
				<?php endif; ?>
				<tr>
					<td>&nbsp;</td>
					<td class="help">&nbsp;</td>
					<td>
						<span class="dashicons dashicons-info"></span>
						<?php
						printf(
							/* translators: %1$s: opening link tag, %2$s: closing link tag */
							esc_html__( 'You can trigger a new integrity check from %1$sthe Tools page%2$s.', 'woocommerce' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ) . '">',
							'</a>'
						);
						?>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Add the recheck tool to the WooCommerce debug tools list.
	 *
	 * Only adds the tool when the stored check result is not a pass,
	 * so that users can trigger a fresh integrity check from the admin UI.
	 *
	 * @internal For exclusive use of WooCommerce, backwards compatibility not guaranteed.
	 * @since 10.6.0
	 *
	 * @param array $tools The existing debug tools array.
	 * @return array The modified tools array.
	 */
	public static function handle_woocommerce_debug_tools( $tools ) {
		$tools['recheck_file_manifest'] = array(
			'name'     => __( 'Installation integrity check', 'woocommerce' ),
			'button'   => __( 'Recheck', 'woocommerce' ),
			'desc'     => __( 'Clears the cached integrity check result so that WooCommerce repeats it on the next page load.', 'woocommerce' ),
			'callback' => array( static::class, 'recheck_tool_callback' ),
		);

		return $tools;
	}

	/**
	 * Callback for the recheck debug tool.
	 *
	 * Deletes the stored check result so that the next page load
	 * triggers a fresh integrity verification.
	 *
	 * @since 10.6.0
	 *
	 * @return string Success message displayed on the tools page.
	 */
	public static function recheck_tool_callback() {
		delete_option( self::CHECK_RESULT_OPTION );
		return __( 'Integrity check will run on the next page load.', 'woocommerce' );
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
	public static function incomplete_installation( $plugin_file, $details = array(), $details_heading = '' ): void {
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
	private static function hide_from_active_plugins( $plugin_file ): void {
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
	 * Show the missing-manifest warning if the environment warrants it.
	 *
	 * Checks the WordPress environment type and calls missing_manifest_warning()
	 * when the site is not local or development.
	 */
	private static function maybe_show_missing_manifest_warning(): void {
		$env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( ! in_array( $env, array( 'local', 'development' ), true ) ) {
			self::missing_manifest_warning();
		}
	}

	/**
	 * Show a warning when the file manifest is missing in a non-development environment.
	 *
	 * WooCommerce still loads normally, but the admin is informed that the
	 * integrity check could not run.
	 */
	private static function missing_manifest_warning(): void {
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
	private static function register_cli_diagnostics(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		// The autoloader is not available at this point, so the file is loaded manually.
		require_once __DIR__ . '/CLI/FileManifestCommand.php';
		$command = new \Automattic\WooCommerce\Internal\CLI\FileManifestCommand();
		$command->register_commands();
	}
}
