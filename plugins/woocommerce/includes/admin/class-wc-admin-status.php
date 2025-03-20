<?php
/**
 * Debug/Status page
 *
 * @package WooCommerce\Admin\System Status
 * @version 2.2.0
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\PageController as LoggingPageController;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Status Class.
 */
class WC_Admin_Status {
	/**
	 * An instance of the DB log handler list table.
	 *
	 * @var WC_Admin_Log_Table_List
	 */
	private static $db_log_list_table;

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		include_once __DIR__ . '/views/html-admin-page-status.php';
	}

	/**
	 * Handles output of report.
	 */
	public static function status_report() {
		include_once __DIR__ . '/views/html-admin-page-status-report.php';
	}

	/**
	 * Handles output of tools.
	 */
	public static function status_tools() {
		if ( ! class_exists( 'WC_REST_System_Status_Tools_Controller' ) ) {
			wp_die( 'Cannot load the REST API to access WC_REST_System_Status_Tools_Controller.' );
		}

		$tools                 = self::get_tools();
		$tool_requires_refresh = false;

		if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'debug_action' ) ) { // WPCS: input var ok, sanitization ok.
			$tools_controller = new WC_REST_System_Status_Tools_Controller();
			$action           = wc_clean( wp_unslash( $_GET['action'] ) ); // WPCS: input var ok.

			if ( array_key_exists( $action, $tools ) ) {
				$response = $tools_controller->execute_tool( $action );

				$tool                  = $tools[ $action ];
				$tool_requires_refresh = $tool['requires_refresh'] ?? false;
				$tool                  = array(
					'id'          => $action,
					'name'        => $tool['name'],
					'action'      => $tool['button'],
					'description' => $tool['desc'],
					'disabled'    => $tool['disabled'] ?? false,
				);
				$tool                  = array_merge( $tool, $response );

				/**
				 * Fires after a WooCommerce system status tool has been executed.
				 *
				 * @param array  $tool  Details about the tool that has been executed.
				 */
				do_action( 'woocommerce_system_status_tool_executed', $tool );
			} else {
				$response = array(
					'success' => false,
					'message' => __( 'Tool does not exist.', 'woocommerce' ),
				);
			}

			if ( $response['success'] ) {
				echo '<div class="updated inline"><p>' . esc_html( $response['message'] ) . '</p></div>';
			} else {
				echo '<div class="error inline"><p>' . esc_html( $response['message'] ) . '</p></div>';
			}
		}

		// Display message if settings settings have been saved.
		if ( isset( $_REQUEST['settings-updated'] ) ) { // WPCS: input var ok.
			echo '<div class="updated inline"><p>' . esc_html__( 'Your changes have been saved.', 'woocommerce' ) . '</p></div>';
		}

		if ( $tool_requires_refresh ) {
			$tools = self::get_tools();
		}

		include_once __DIR__ . '/views/html-admin-page-status-tools.php';
	}

	/**
	 * Get tools.
	 *
	 * @return array of tools
	 */
	public static function get_tools() {
		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		return $tools_controller->get_tools();
	}

	/**
	 * Show the logs page.
	 */
	public static function status_logs() {
		wc_get_container()->get( LoggingPageController::class )->render();
	}

	/**
	 * Show the log page contents for file log handler.
	 */
	public static function status_logs_file() {
		$logs = self::scan_log_files();

		if ( ! empty( $_REQUEST['log_file'] ) && isset( $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ] ) ) { // WPCS: input var ok, CSRF ok.
			$viewed_log = $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ]; // WPCS: input var ok, CSRF ok.
		} elseif ( ! empty( $logs ) ) {
			$viewed_log = current( $logs );
		}

		$handle = ! empty( $viewed_log ) ? self::get_log_file_handle( $viewed_log ) : '';

		if ( ! empty( $_REQUEST['handle'] ) ) { // WPCS: input var ok, CSRF ok.
			self::remove_log();
		}

		include_once __DIR__ . '/views/html-admin-page-status-logs.php';
	}

	/**
	 * Show the log page contents for db log handler.
	 */
	public static function status_logs_db() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce handled in flush_db_logs().
		if ( isset( $_REQUEST['flush-logs'] ) ) {
			self::flush_db_logs();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce handled in log_table_bulk_actions().
		if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['log'] ) ) {
			self::log_table_bulk_actions();
		}

		$log_table_list = self::get_db_log_list_table();
		$log_table_list->prepare_items();

		include_once __DIR__ . '/views/html-admin-page-status-logs-db.php';
	}

	/**
	 * Retrieve metadata from a file. Based on WP Core's get_file_data function.
	 *
	 * @since  2.1.1
	 * @param  string $file Path to the file.
	 * @return string
	 */
	public static function get_file_version( $file ) {

		// Avoid notices if file does not exist.
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' ); // @codingStandardsIgnoreLine.

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 ); // @codingStandardsIgnoreLine.

		// PHP will close file handle, but we are good citizens.
		fclose( $fp ); // @codingStandardsIgnoreLine.

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	/**
	 * Return the log file handle.
	 *
	 * @param string $filename Filename to get the handle for.
	 * @return string
	 */
	public static function get_log_file_handle( $filename ) {
		return substr( $filename, 0, strlen( $filename ) > 48 ? strlen( $filename ) - 48 : strlen( $filename ) - 4 );
	}

	/**
	 * Scan the template files.
	 *
	 * @param  string $template_path Path to the template directory.
	 * @return array
	 */
	public static function scan_template_files( $template_path ) {
		$files  = is_string( $template_path ) ? @scandir( $template_path ) : array(); // @codingStandardsIgnoreLine.
		$result = array();

		if ( ! empty( $files ) ) {

			foreach ( $files as $key => $value ) {

				if ( ! in_array( $value, array( '.', '..' ), true ) ) {

					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Scan the log files.
	 *
	 * @return array
	 */
	public static function scan_log_files() {
		return WC_Log_Handler_File::get_log_files();
	}

	/**
	 * Get latest version of a theme by slug.
	 *
	 * @param  object $theme WP_Theme object.
	 * @return string Version number if found.
	 */
	public static function get_latest_theme_version( $theme ) {
		include_once ABSPATH . 'wp-admin/includes/theme.php';

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $theme->get_stylesheet(),
				'fields' => array(
					'sections' => false,
					'tags'     => false,
				),
			)
		);

		$update_theme_version = 0;

		// Check .org for updates.
		if ( is_object( $api ) && ! is_wp_error( $api ) && isset( $api->version ) ) {
			$update_theme_version = $api->version;
		} elseif ( strstr( $theme->{'Author URI'}, 'woothemes' ) ) { // Check WooThemes Theme Version.
			$theme_dir          = substr( strtolower( str_replace( ' ', '', $theme->Name ) ), 0, 45 ); // @codingStandardsIgnoreLine.
			$theme_version_data = get_transient( $theme_dir . '_version_data' );

			if ( false === $theme_version_data ) {
				$theme_changelog = wp_safe_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $theme_dir . '/changelog.txt' );
				$cl_lines        = explode( "\n", wp_remote_retrieve_body( $theme_changelog ) );
				if ( ! empty( $cl_lines ) ) {
					foreach ( $cl_lines as $line_num => $cl_line ) {
						if ( preg_match( '/^[0-9]/', $cl_line ) ) {
							$theme_date         = str_replace( '.', '-', trim( substr( $cl_line, 0, strpos( $cl_line, '-' ) ) ) );
							$theme_version      = preg_replace( '~[^0-9,.]~', '', stristr( $cl_line, 'version' ) );
							$theme_update       = trim( str_replace( '*', '', $cl_lines[ $line_num + 1 ] ) );
							$theme_version_data = array(
								'date'      => $theme_date,
								'version'   => $theme_version,
								'update'    => $theme_update,
								'changelog' => $theme_changelog,
							);
							set_transient( $theme_dir . '_version_data', $theme_version_data, DAY_IN_SECONDS );
							break;
						}
					}
				}
			}

			if ( ! empty( $theme_version_data['version'] ) ) {
				$update_theme_version = $theme_version_data['version'];
			}
		}

		return $update_theme_version;
	}

	/**
	 * Remove/delete the chosen file.
	 */
	public static function remove_log() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'remove_log' ) ) { // WPCS: input var ok, sanitization ok.
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		if ( ! empty( $_REQUEST['handle'] ) ) {  // WPCS: input var ok.
			$log_handler = new WC_Log_Handler_File();
			$log_handler->remove( wp_unslash( $_REQUEST['handle'] ) ); // WPCS: input var ok, sanitization ok.
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=wc-status&tab=logs' ) ) );
		exit();
	}

	/**
	 * Return a stored instance of the DB log list table class.
	 *
	 * @return WC_Admin_Log_Table_List
	 */
	public static function get_db_log_list_table() {
		if ( is_null( self::$db_log_list_table ) ) {
			self::$db_log_list_table = new WC_Admin_Log_Table_List();
		}

		return self::$db_log_list_table;
	}


	/**
	 * Clear DB log table.
	 *
	 * @since 3.0.0
	 */
	private static function flush_db_logs() {
		check_admin_referer( 'bulk-logs' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage log entries.', 'woocommerce' ) );
		}

		WC_Log_Handler_DB::flush();

		$sendback = wp_sanitize_redirect( admin_url( 'admin.php?page=wc-status&tab=logs' ) );

		wp_safe_redirect( $sendback );
		exit;
	}

	/**
	 * Bulk DB log table actions.
	 *
	 * @since 3.0.0
	 */
	private static function log_table_bulk_actions() {
		check_admin_referer( 'bulk-logs' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage log entries.', 'woocommerce' ) );
		}

		$log_ids = (array) filter_input( INPUT_GET, 'log', FILTER_CALLBACK, array( 'options' => 'absint' ) );

		$action = self::get_db_log_list_table()->current_action();

		if ( 'delete' === $action ) {
			WC_Log_Handler_DB::delete( $log_ids );

			$sendback = remove_query_arg( array( 'action', 'action2', 'log', '_wpnonce', '_wp_http_referer' ), wp_get_referer() );

			wp_safe_redirect( $sendback );
			exit();
		}
	}

	/**
	 * Prints table info if a base table is not present.
	 */
	private static function output_tables_info() {
		$missing_tables = WC_Install::verify_base_tables( false );
		if ( 0 === count( $missing_tables ) ) {
			return;
		}
		?>

		<br>
		<strong style="color:#a00;">
			<span class="dashicons dashicons-warning"></span>
			<?php
				echo esc_html(
					sprintf(
					// translators: Comma separated list of missing tables.
						__( 'Missing base tables: %s. Some WooCommerce functionality may not work as expected.', 'woocommerce' ),
						implode( ', ', $missing_tables )
					)
				);
			?>
		</strong>

		<?php
	}

	/**
	 * Prints the information about plugins for the system status report.
	 * Used for both active and inactive plugins sections.
	 *
	 * @param array $plugins List of plugins to display.
	 * @param array $untested_plugins List of plugins that haven't been tested with the current WooCommerce version.
	 * @return void
	 */
	private static function output_plugins_info( $plugins, $untested_plugins ) {
		$wc_version = Constants::get_constant( 'WC_VERSION' );

		if ( 'major' === Constants::get_constant( 'WC_SSR_PLUGIN_UPDATE_RELEASE_VERSION_TYPE' ) ) {
			// Since we're only testing against major, we don't need to show minor and patch version.
			$wc_version = $wc_version[0] . '.0';
		}

		foreach ( $plugins as $plugin ) {
			if ( ! empty( $plugin['name'] ) ) {
				// Link the plugin name to the plugin url if available.
				$plugin_name = esc_html( $plugin['name'] );
				if ( ! empty( $plugin['url'] ) ) {
					$plugin_name = '<a href="' . esc_url( $plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'woocommerce' ) . '" target="_blank">' . $plugin_name . '</a>';
				}

				$has_newer_version = false;
				$version_string    = $plugin['version'];
				$network_string    = '';
				if ( strstr( $plugin['url'], 'woothemes.com' ) || strstr( $plugin['url'], 'woocommerce.com' ) || strstr( $plugin['url'], 'woo.com' ) ) {
					if ( ! empty( $plugin['version_latest'] ) && version_compare( $plugin['version_latest'], $plugin['version'], '>' ) ) {
						/* translators: 1: current version. 2: latest version */
						$version_string = sprintf( __( '%1$s (update to version %2$s is available)', 'woocommerce' ), $plugin['version'], $plugin['version_latest'] );
					}

					if ( false !== $plugin['network_activated'] ) {
						$network_string = ' &ndash; <strong style="color: black;">' . esc_html__( 'Network enabled', 'woocommerce' ) . '</strong>';
					}
				}
				$untested_string = '';
				if ( array_key_exists( $plugin['plugin'], $untested_plugins ) ) {
					$untested_string = ' &ndash; <strong style="color: #a00;">';

					/* translators: %s: version */
					$untested_string .= esc_html( sprintf( __( 'Installed version not tested with active version of WooCommerce %s', 'woocommerce' ), $wc_version ) );

					$untested_string .= '</strong>';
				}
				?>
				<tr>
					<td><?php echo wp_kses_post( $plugin_name ); ?></td>
					<td class="help">&nbsp;</td>
					<td>
						<?php
						/* translators: %s: plugin author */
						printf( esc_html__( 'by %s', 'woocommerce' ), esc_html( $plugin['author_name'] ) );
						echo ' &ndash; ' . esc_html( $version_string ) . $untested_string . $network_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</td>
				</tr>
				<?php
			}
		}
	}
}
