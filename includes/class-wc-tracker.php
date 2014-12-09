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
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Tracker' ) ) :

class WC_Tracker {

	/**
	 * URL to the WooThemes Tracker API endpoint
	 * @var string
	 */
	public $api_url = 'http://tracking.woocommerce.com/wc-api/tracker/';

	/**
	 * Constructor
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'add_tracking_event_to_cron_schedule' ) );
		add_action( 'wc_tracker_send_event', array( $this, 'send_tracking_data' ) );
		add_action( 'admin_notices', array( $this, 'admin_optin_notice' ) );
		add_action( 'admin_init', array( $this, 'check_optin_action' ) );
	}

	/**
	 * Schedule daily cron to check if tracking data must be sent
	 * @return void
	 */
	public function add_tracking_event_to_cron_schedule () {
		if ( ! wp_next_scheduled( 'wc_tracker_send_event' ) ) {
			wp_schedule_event( time(), apply_filters( 'woocommerce_tracker_event_recurrence', 'daily' ), 'wc_tracker_send_event' );
		}
	}

	/**
	 * Decide whether to send tracking data or not
	 * @param  boolean $override
	 * @return void
	 */
	public function send_tracking_data( $override = false ) {
		if ( ! apply_filters( 'woocommerce_tracker_send_override', $override ) ) {
			// User must opt in to send
			if ( ! get_option( 'woocommerce_allow_tracking' ) ) {
				return;
			}

			// Send a maximum of once per week by default.
			$last_send = $this->get_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'woocommerce_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		}

		$params = $this->get_tracking_data();

		$response = wp_remote_post( esc_url( apply_filters( 'woocommerce_tracker_api_url', $this->api_url ) ), array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array( 'user-agent' => 'WooCommerce/' . WC_VERSION . '; ' . esc_url( home_url( '/' ) ) ),
				'body' => json_encode( $params ),
				'cookies' => array()
			)
		);
		if ( ! is_wp_error( $response ) && '200' == wp_remote_retrieve_response_code( $response ) ) {
			update_option( 'woocommerce_tracker_last_send', time() );
		}
	}

	/**
	 * Get the last time tracking data was sent
	 * @return int|bool
	 */
	public function get_last_send_time() {
		return apply_filters( 'woocommerce_tracker_last_send_time', get_option( 'woocommerce_tracker_last_send', false ) );
	}

	/**
	 * Get all the tracking data
	 * @return array
	 */
	public function get_tracking_data() {
		$data = array();

		// General site info
		$data['url']   = home_url();
		$data['email'] = apply_filters( 'woocommerce_tracker_admin_email', get_option( 'admin_email' ) );
		$data['theme'] = $this->get_theme_info();

		// WordPress Info
		$data['wp'] = $this->get_wordpress_info();

		// Server Info
		$data['server'] = $this->get_server_info();

		// Plugin info
		$all_plugins = $this->get_all_plugins();
		$data['active_plugins']   = $all_plugins['active_plugins'];
		$data['inactive_plugins'] = $all_plugins['inactive_plugins'];

		// Store count info
		$data['users']    = $this->get_user_counts();
		$data['products'] = $this->get_product_counts();
		$data['orders']   = $this->get_order_counts();

		// Payment gateway info
		$data['gateways'] = $this->get_active_payment_gateways();

		// Shipping method info
		$data['shipping_methods'] = $this->get_active_shipping_methods();

		// Get all WooCommerce options info
		$data['settings'] = $this->get_all_woocommerce_options_values();

		// Template overrides
		$data['template_overrides'] = $this->get_all_template_overrides();

		return apply_filters( 'woocommerce_tracker_data', $data );
	}

	/**
	 * Get the current theme info, theme name and version
	 * @return array
	 */
	public function get_theme_info() {
		if ( get_bloginfo( 'version' ) < '3.4' ) {
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
	public function get_wordpress_info() {
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
	public function get_server_info() {
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
	public function get_all_plugins() {
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
	public function get_user_counts() {
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
	public function get_product_counts() {
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
	public function get_order_counts() {
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
	public function get_active_payment_gateways() {
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
	public function get_active_shipping_methods() {
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
	public function get_all_woocommerce_options_values() {
		return array(
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
	public function get_all_template_overrides() {
		$override_data  = array();
		$template_paths = apply_filters( 'woocommerce_template_overrides_scan_paths', array( 'WooCommerce' => WC()->plugin_path() . '/templates/' ) );
		$scanned_files  = array();
		$found_files    = array();
		$status         = require_once( WC()->plugin_path() . '/includes/admin/class-wc-admin-status.php' );

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

	/**
	 * Output admin notice to opt in or out of tracking.
	 * @return void
	 */
	public function admin_optin_notice() {
		if ( get_option( 'woocommerce_hide_tracking_notice' ) ) {
			return;
		}

		if ( get_option( 'woocommerce_allow_tracking' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		echo '<div id="message" class="updated woocommerce-message wc-connect">';
		echo '<p>' . __( 'Allow WooCommerce usage tracking? Send non sensitive WooCommerce usage data to WooThemes and get 20% discount on your next WooThemes purchase.', 'woocommerce' ) . '</p>';
		echo '<p class="submit"><a href="' . esc_url( wp_nonce_url( add_query_arg( 'wc_tracker', 'opt-in' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ) . '" class="button-primary">' . __( 'Allow', 'woocommerce' ) . '</a>';
		echo '&nbsp;<a href="' . esc_url( wp_nonce_url( add_query_arg( 'wc_tracker', 'opt-out' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ) . '" class="skip button-primary">' . __( 'No, don\'t bother me again', 'woocommerce' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Handle opt in or out actions based on notice selection
	 * @return void
	 */
	public function check_optin_action() {
		if ( ! isset( $_GET['wc_tracker'] ) || ! isset( $_GET['wc_tracker_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optin' ) ) {
			return;
		}

		if ( 'opt-in' == $_GET['wc_tracker'] ) {
			update_option( 'woocommerce_allow_tracking', true );
			update_option( 'woocommerce_hide_tracking_notice', true );
			$this->send_tracking_data( true );
		} elseif ( 'opt-out' == $_GET['wc_tracker'] ) {
			update_option( 'woocommerce_allow_tracking', false );
			update_option( 'woocommerce_hide_tracking_notice', true );
		}
	}
}

endif;

return new WC_Tracker;