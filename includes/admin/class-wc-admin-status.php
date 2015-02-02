<?php
/**
 * Debug/Status page
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/System Status
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Status Class
 */
class WC_Admin_Status {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		include_once( 'views/html-admin-page-status.php' );
	}

	/**
	 * Handles output of report
	 */
	public static function status_report() {
		include_once( 'views/html-admin-page-status-report.php' );
	}

	/**
	 * Handles output of tools
	 */
	public static function status_tools() {
		global $wpdb;

		$tools = self::get_tools();

		if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {

			switch ( $_GET['action'] ) {
				case 'clear_transients' :
					wc_delete_product_transients();
					wc_delete_shop_order_transients();
					WC_Cache_Helper::get_transient_version( 'shipping', true );

					echo '<div class="updated"><p>' . __( 'Product Transients Cleared', 'woocommerce' ) . '</p></div>';
				break;
				case 'clear_expired_transients' :

					// http://w-shadow.com/blog/2012/04/17/delete-stale-transients/
					$rows = $wpdb->query( "
						DELETE
							a, b
						FROM
							{$wpdb->options} a, {$wpdb->options} b
						WHERE
							a.option_name LIKE '_transient_%' AND
							a.option_name NOT LIKE '_transient_timeout_%' AND
							b.option_name = CONCAT(
								'_transient_timeout_',
								SUBSTRING(
									a.option_name,
									CHAR_LENGTH('_transient_') + 1
								)
							)
							AND b.option_value < UNIX_TIMESTAMP()
					" );

					$rows2 = $wpdb->query( "
						DELETE
							a, b
						FROM
							{$wpdb->options} a, {$wpdb->options} b
						WHERE
							a.option_name LIKE '_site_transient_%' AND
							a.option_name NOT LIKE '_site_transient_timeout_%' AND
							b.option_name = CONCAT(
								'_site_transient_timeout_',
								SUBSTRING(
									a.option_name,
									CHAR_LENGTH('_site_transient_') + 1
								)
							)
							AND b.option_value < UNIX_TIMESTAMP()
					" );

					echo '<div class="updated"><p>' . sprintf( __( '%d Transients Rows Cleared', 'woocommerce' ), $rows + $rows2 ) . '</p></div>';

				break;
				case 'reset_roles' :
					// Remove then re-add caps and roles
					WC_Install::remove_roles();
					WC_Install::create_roles();

					echo '<div class="updated"><p>' . __( 'Roles successfully reset', 'woocommerce' ) . '</p></div>';
				break;
				case 'recount_terms' :

					$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

					_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );

					$product_tags = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

					_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );

					echo '<div class="updated"><p>' . __( 'Terms successfully recounted', 'woocommerce' ) . '</p></div>';
				break;
				case 'clear_sessions' :

					$wpdb->query( "
						DELETE FROM {$wpdb->options}
						WHERE option_name LIKE '_wc_session_%' OR option_name LIKE '_wc_session_expires_%'
					" );

					wp_cache_flush();

					echo '<div class="updated"><p>' . __( 'Sessions successfully cleared', 'woocommerce' ) . '</p></div>';
				break;
				case 'install_pages' :
					WC_Install::create_pages();
					echo '<div class="updated"><p>' . __( 'All missing WooCommerce pages was installed successfully.', 'woocommerce' ) . '</p></div>';
				break;
				case 'delete_taxes' :

					$wpdb->query( "TRUNCATE " . $wpdb->prefix . "woocommerce_tax_rates" );

					$wpdb->query( "TRUNCATE " . $wpdb->prefix . "woocommerce_tax_rate_locations" );

					echo '<div class="updated"><p>' . __( 'Tax rates successfully deleted', 'woocommerce' ) . '</p></div>';
				break;
				case 'reset_tracking' :
					delete_option( 'woocommerce_allow_tracking' );
					WC_Admin_Notices::add_notice( 'tracking' );

					echo '<div class="updated"><p>' . __( 'Usage tracking settings successfully reset.', 'woocommerce' ) . '</p></div>';
				break;
				default :
					$action = esc_attr( $_GET['action'] );
					if ( isset( $tools[ $action ]['callback'] ) ) {
						$callback = $tools[ $action ]['callback'];
						$return = call_user_func( $callback );
						if ( $return === false ) {
							if ( is_array( $callback ) ) {
								echo '<div class="error"><p>' . sprintf( __( 'There was an error calling %s::%s', 'woocommerce' ), get_class( $callback[0] ), $callback[1] ) . '</p></div>';

							} else {
								echo '<div class="error"><p>' . sprintf( __( 'There was an error calling %s', 'woocommerce' ), $callback ) . '</p></div>';
							}
						}
					}
				break;
			}
		}

		// Manual translation update messages
		if ( isset( $_GET['translation_updated'] ) ) {
			switch ( $_GET['translation_updated'] ) {
				case 2 :
					echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woocommerce' ) . ' ' . __( 'Seems you don\'t have permission to do this!', 'woocommerce' ) . '</p></div>';
					break;
				case 3 :
					echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woocommerce' ) . ' ' . sprintf( __( 'An authentication error occurred while updating the translation. Please try again or configure your %sUpgrade Constants%s.', 'woocommerce' ), '<a href="http://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants">', '</a>' ) . '</p></div>';
					break;
				case 4 :
					echo '<div class="error"><p>' . __( 'Failed to install/update the translation:', 'woocommerce' ) . ' ' . __( 'Sorry but there is no translation available for your language =/', 'woocommerce' ) . '</p></div>';
					break;

				default :
					// Force WordPress find for new updates and hide the WooCommerce translation update
					set_site_transient( 'update_plugins', null );

					echo '<div class="updated"><p>' . __( 'Translations installed/updated successfully!', 'woocommerce' ) . '</p></div>';
					break;
			}
		}

		// Display message if settings settings have been saved
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			echo '<div class="updated"><p>' . __( 'Your changes have been saved.', 'woocommerce' ) . '</p></div>';
		}

		include_once( 'views/html-admin-page-status-tools.php' );
	}

	/**
	 * Get tools
	 *
	 * @return array of tools
	 */
	public static function get_tools() {
		$tools = array(
			'clear_transients' => array(
				'name'    => __( 'WC Transients','woocommerce'),
				'button'  => __('Clear transients','woocommerce'),
				'desc'    => __( 'This tool will clear the product/shop transients cache.', 'woocommerce' ),
			),
			'clear_expired_transients' => array(
				'name'    => __( 'Expired Transients','woocommerce'),
				'button'  => __('Clear expired transients','woocommerce'),
				'desc'    => __( 'This tool will clear ALL expired transients from WordPress.', 'woocommerce' ),
			),
			'recount_terms' => array(
				'name'    => __('Term counts','woocommerce'),
				'button'  => __('Recount terms','woocommerce'),
				'desc'    => __( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', 'woocommerce' ),
			),
			'reset_roles' => array(
				'name'    => __('Capabilities','woocommerce'),
				'button'  => __('Reset capabilities','woocommerce'),
				'desc'    => __( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ),
			),
			'clear_sessions' => array(
				'name'    => __('Customer Sessions','woocommerce'),
				'button'  => __('Clear all sessions','woocommerce'),
				'desc'    => __( '<strong class="red">Warning:</strong> This tool will delete all customer session data from the database, including any current live carts.', 'woocommerce' ),
			),
			'install_pages' => array(
				'name'    => __( 'Install WooCommerce Pages', 'woocommerce' ),
				'button'  => __( 'Install pages', 'woocommerce' ),
				'desc'    => __( '<strong class="red">Note:</strong> This tool will install all the missing WooCommerce pages. Pages already defined and set up will not be replaced.', 'woocommerce' ),
			),
			'delete_taxes' => array(
				'name'    => __( 'Delete all WooCommerce tax rates', 'woocommerce' ),
				'button'  => __( 'Delete ALL tax rates', 'woocommerce' ),
				'desc'    => __( '<strong class="red">Note:</strong> This option will delete ALL of your tax rates, use with caution.', 'woocommerce' ),
			),
			'reset_tracking' => array(
				'name'    => __( 'Reset Usage Tracking Settings', 'woocommerce' ),
				'button'  => __( 'Reset usage tracking settings', 'woocommerce' ),
				'desc'    => __( 'This will reset your usage tracking settings, causing it to show the opt-in banner again and not sending any data.', 'woocommerce' ),
			)
		);

		if ( get_locale() !== 'en_US' ) {
			$tools['translation_upgrade'] = array(
				'name'    => __( 'Translation Upgrade', 'woocommerce' ),
				'button'  => __( 'Force Translation Upgrade', 'woocommerce' ),
				'desc'    => __( '<strong class="red">Note:</strong> This option will force the translation upgrade for your language if a translation is available.', 'woocommerce' ),
			);
		}

		return apply_filters( 'woocommerce_debug_tools', $tools );
	}

	/**
	 * Show the logs page
	 */
	public static function status_logs() {

		$logs = self::scan_log_files();

		if ( ! empty( $_REQUEST['log_file'] ) && isset( $logs[ sanitize_title( $_REQUEST['log_file'] ) ] ) ) {
			$viewed_log = $logs[ sanitize_title( $_REQUEST['log_file'] ) ];
		} elseif ( $logs ) {
			$viewed_log = current( $logs );
		}

		include_once( 'views/html-admin-page-status-logs.php' );
	}

	/**
	 * Retrieve metadata from a file. Based on WP Core's get_file_data function
	 *
	 * @since 2.1.1
	 * @param string $file Path to the file
	 * @return string
	 */
	public static function get_file_version( $file ) {

		// Avoid notices if file does not exist
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' );

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 );

		// PHP will close file handle, but we are good citizens.
		fclose( $fp );

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] )
			$version = _cleanup_header_comment( $match[1] );

		return $version ;
	}

	/**
	 * Scan the template files
	 *
	 * @param string $template_path
	 * @return array
	 */
	public static function scan_template_files( $template_path ) {

		$files         = scandir( $template_path );
		$result        = array();

		if ( $files ) {

			foreach ( $files as $key => $value ) {

				if ( ! in_array( $value, array( ".",".." ) ) ) {

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
	 * Scan the log files
	 *
	 * @return array
	 */
	public static function scan_log_files() {
		$files         = @scandir( WC_LOG_DIR );
		$result        = array();

		if ( $files ) {

			foreach ( $files as $key => $value ) {

				if ( ! in_array( $value, array( '.', '..' ) ) ) {
					if ( ! is_dir( $value ) && strstr( $value, '.log' ) ) {
						$result[ sanitize_title( $value ) ] = $value;
					}
				}
			}
		}
		return $result;
	}
}
