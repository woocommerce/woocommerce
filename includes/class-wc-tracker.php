<?php
/**
 * WooCommerce Tracker
 *
 * The WooCommerce tracker class adds functionality to track WooCommerce usage based on if the customer opted in.
 * No personal infomation is tracked, only general WooCommerce settings, general product, order and user counts and admin email for discount code.
 *
 * @class 		WC_Tracker
 * @version		2.3.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Tracker {

	/**
	 * URL to the WooThemes Tracker API endpoint
	 * @var string
	 */
	private static $api_url = 'https://tracking.woocommerce.com/v1/';

	/**
	 * Hook into cron event
	 */
	public static function init() {
		add_action( 'woocommerce_tracker_send_event', array( __CLASS__, 'send_tracking_data' ) );
	}

	/**
	 * Decide whether to send tracking data or not
	 * @param  boolean $override
	 * @return void
	 */
	public static function send_tracking_data( $override = false ) {
		// Dont trigger this on AJAX Requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! apply_filters( 'woocommerce_tracker_send_override', $override ) ) {
			// Send a maximum of once per week by default.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'woocommerce_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we dont want duplicate calls due to double clicking links.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		// Update time first before sending to ensure it is set
		update_option( 'woocommerce_tracker_last_send', time() );

		$params   = self::get_tracking_data();
		$response = wp_safe_remote_post( self::$api_url, array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => false,
				'headers'     => array( 'user-agent' => 'WooCommerceTracker/' . md5( esc_url( home_url( '/' ) ) ) . ';' ),
				'body'        => json_encode( $params ),
				'cookies'     => array()
			)
		);
	}

	/**
	 * Get the last time tracking data was sent
	 * @return int|bool
	 */
	private static function get_last_send_time() {
		return apply_filters( 'woocommerce_tracker_last_send_time', get_option( 'woocommerce_tracker_last_send', false ) );
	}

	/**
	 * Get all the tracking data
	 * @return array
	 */
	private static function get_tracking_data() {
		$data                       = array();

		// General site info
		$data['url']                = home_url();
		$data['email']              = apply_filters( 'woocommerce_tracker_admin_email', get_option( 'admin_email' ) );
		$data['theme']              = self::get_theme_info();

		// WordPress Info
		$data['wp']                 = self::get_wordpress_info();

		// Server Info
		$data['server']             = self::get_server_info();

		// Plugin info
		$all_plugins                = self::get_all_plugins();
		$data['active_plugins']     = $all_plugins['active_plugins'];
		$data['inactive_plugins']   = $all_plugins['inactive_plugins'];

		// Store count info
		$data['users']              = self::get_user_counts();
		$data['products']           = self::get_product_counts();
		$data['orders']             = self::get_order_counts();

		// Payment gateway info
		$data['gateways']           = self::get_active_payment_gateways();

		// Shipping method info
		$data['shipping_methods']   = self::get_active_shipping_methods();

		// Get all WooCommerce options info
		$data['settings']           = self::get_all_woocommerce_options_values();

		// Template overrides
		$data['template_overrides'] = self::get_all_template_overrides();

		return apply_filters( 'woocommerce_tracker_data', $data );
	}

	/**
	 * Get the current theme info, theme name and version
	 * @return array
	 */
	public static function get_theme_info() {
		$wp_version = get_bloginfo( 'version' );

		if ( version_compare( $wp_version, '3.4', '<' ) ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme_name = $theme_data['Name'];
			$theme_version = $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme_name = $theme_data->Name;
			$theme_version = $theme_data->Version;
		}
		$theme_child_theme = is_child_theme() ? 'Yes' : 'No';
		$theme_wc_support = ( ! current_theme_supports( 'woocommerce' ) && ! in_array( $theme_data->template, wc_get_core_supported_themes() ) ) ? 'No' : 'Yes';

		return array( 'name' => $theme_name, 'version' => $theme_version, 'child_theme' => $theme_child_theme, 'wc_support' => $theme_wc_support );
	}

	/**
	 * Get WordPress related data.
	 * @return array
	 */
	private static function get_wordpress_info() {
		$wp_data = array();

		$memory = wc_let_to_num( WP_MEMORY_LIMIT );
		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode'] = ( defined('WP_DEBUG') && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale'] = get_locale();
		$wp_data['version'] = get_bloginfo( 'version' );
		$wp_data['multisite'] = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}

	/**
	 * Get server related info
	 * @return array
	 */
	private static function get_server_info() {
		$server_data = array();

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE'];
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data['php_version'] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) {
			$server_data['php_post_max_size'] = size_format( wc_let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt'] = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin'] = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		global $wpdb;
		$server_data['mysql_version'] = $wpdb->db_version();

		$server_data['php_max_upload_size'] = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap'] = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen'] = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl'] = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Get all plugins grouped into activated or not
	 * @return array
	 */
	private static function get_all_plugins() {
		// Ensure get_plugins function is loaded
		if( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        	 = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins 	 = array();

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted = array();
			$formatted['name'] = strip_tags( $v['Name'] );
			if ( isset( $v['Version'] ) ) {
				$formatted['version'] = strip_tags( $v['Version'] );
			}
			if ( isset( $v['Author'] ) ) {
				$formatted['author'] = strip_tags( $v['Author'] );
			}
			if ( isset( $v['Network'] ) ) {
				$formatted['network'] = strip_tags( $v['Network'] );
			}
			if ( isset( $v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = strip_tags( $v['PluginURI'] );
			}
			if ( in_array( $k, $active_plugins_keys ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[$k] );
				$active_plugins[$k] = $formatted;
			} else {
				$plugins[$k] = $formatted;
			}
		}

		return array( 'active_plugins' => $active_plugins, 'inactive_plugins' => $plugins );
	}

	/**
	 * Get user totals based on user role
	 * @return array
	 */
	private static function get_user_counts() {
		$user_count = array();
		$user_count_data = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}

		return $user_count;
	}

	/**
	 * Get product totals based on product type
	 * @return array
	 */
	private static function get_product_counts() {
		$product_count = array();
		$product_count_data = wp_count_posts( 'product' );
		$product_count['total'] = $product_count_data->publish;

		$product_statuses = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $product_statuses as $product_status ) {
			$product_count[ $product_status->name ] = $product_status->count;
		}
		return $product_count;
	}

	/**
	 * Get order counts based on order status
	 * @return array
	 */
	private static function get_order_counts() {
		$order_count = array();
		$order_count_data = wp_count_posts( 'shop_order' );

		foreach ( wc_get_order_statuses() as $status_slug => $status_name ) {
			$order_count[ $status_slug ] = $order_count_data->{ $status_slug };
		}
		return $order_count;
	}

	/**
	 * Get a list of all active payment gateways
	 * @return array
	 */
	private static function get_active_payment_gateways() {
		$active_gateways = array();
		$gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $gateways as $id => $gateway ) {
			if ( isset( $gateway->enabled ) && $gateway->enabled == 'yes' ) {
				$active_gateways[ $id ] = array( 'title' => $gateway->title, 'supports' => $gateway->supports );
			}
		}
		return $active_gateways;
	}

	/**
	 * Get a list of all active shipping methods
	 * @return array
	 */
	private static function get_active_shipping_methods() {
		$active_methods = array();
		$shipping_methods = WC()->shipping->get_shipping_methods();
		foreach ( $shipping_methods as $id => $shipping_method ) {
			if ( isset( $shipping_method->enabled ) && $shipping_method->enabled == 'yes' ) {
				$active_methods[ $id ] = array( 'title' => $shipping_method->title, 'tax_status' => $shipping_method->tax_status );
			}
		}
		return $active_methods;
	}

	/**
	 * Get all options starting with woocommerce_ prefix
	 * @return array
	 */
	private static function get_all_woocommerce_options_values() {
		return array(
			'version'								=> WC()->version,
			'currency'								=> get_woocommerce_currency(),
			'base_location'							=> WC()->countries->get_base_country(),
			'selling_locations'						=> WC()->countries->get_allowed_countries(),
			'api_enabled'							=> get_option( 'woocommerce_api_enabled' ),
			'weight_unit'							=> get_option( 'woocommerce_weight_unit' ),
			'dimension_unit'						=> get_option( 'woocommerce_dimension_unit' ),
			'download_method'						=> get_option( 'woocommerce_file_download_method' ),
			'download_require_login'				=> get_option( 'woocommerce_downloads_require_login' ),
			'calc_taxes'							=> get_option( 'woocommerce_calc_taxes' ),
			'coupons_enabled'						=> get_option( 'woocommerce_enable_coupons' ),
			'guest_checkout'						=> get_option( 'woocommerce_enable_guest_checkout'),
			'secure_checkout'						=> get_option( 'woocommerce_force_ssl_checkout' ),
			'enable_signup_and_login_from_checkout'	=> get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
			'enable_myaccount_registration'			=> get_option( 'woocommerce_enable_myaccount_registration' ),
			'registration_generate_username'		=> get_option( 'woocommerce_registration_generate_username' ),
			'registration_generate_password'		=> get_option( 'woocommerce_registration_generate_password' ),
		);
	}

	/**
	 * Look for any template override and return filenames
	 * @return array
	 */
	private static function get_all_template_overrides() {
		$override_data  = array();
		$template_paths = apply_filters( 'woocommerce_template_overrides_scan_paths', array( 'WooCommerce' => WC()->plugin_path() . '/templates/' ) );
		$scanned_files  = array();

		require_once( WC()->plugin_path() . '/includes/admin/class-wc-admin-status.php' );

		foreach ( $template_paths as $plugin_name => $template_path ) {
			$scanned_files[ $plugin_name ] = WC_Admin_Status::scan_template_files( $template_path );
		}

		foreach ( $scanned_files as $plugin_name => $files ) {
			foreach ( $files as $file ) {
				if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/' . $file;
				} elseif ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
				} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
					$theme_file = get_template_directory() . '/' . $file;
				} elseif( file_exists( get_template_directory() . '/woocommerce/' . $file ) ) {
					$theme_file = get_template_directory() . '/woocommerce/' . $file;
				} else {
					$theme_file = false;
				}
				if ( $theme_file ) {
					$override_data[] = basename( $theme_file );
				}
			}
		}
		return $override_data;
	}
}

WC_Tracker::init();
