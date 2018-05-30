<?php
/**
 * WooCommerce Tracker
 *
 * The WooCommerce tracker class adds functionality to track WooCommerce usage based on if the customer opted in.
 * No personal information is tracked, only general WooCommerce settings, general product, order and user counts and admin email for discount code.
 *
 * @class WC_Tracker
 * @since 2.3.0
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Tracker Class
 */
class WC_Tracker {

	/**
	 * URL to the WooThemes Tracker API endpoint.
	 *
	 * @var string
	 */
	private static $api_url = 'https://tracking.woocommerce.com/v1/';

	/**
	 * Hook into cron event.
	 */
	public static function init() {
		add_action( 'woocommerce_tracker_send_event', array( __CLASS__, 'send_tracking_data' ) );
	}

	/**
	 * Decide whether to send tracking data or not.
	 *
	 * @param boolean $override Should override?.
	 */
	public static function send_tracking_data( $override = false ) {
		// Don't trigger this on AJAX Requests.
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
			// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		// Update time first before sending to ensure it is set.
		update_option( 'woocommerce_tracker_last_send', time() );

		$params = self::get_tracking_data();
		wp_safe_remote_post( self::$api_url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false,
			'headers'     => array( 'user-agent' => 'WooCommerceTracker/' . md5( esc_url_raw( home_url( '/' ) ) ) . ';' ),
			'body'        => wp_json_encode( $params ),
			'cookies'     => array(),
		) );
	}

	/**
	 * Get the last time tracking data was sent.
	 *
	 * @return int|bool
	 */
	private static function get_last_send_time() {
		return apply_filters( 'woocommerce_tracker_last_send_time', get_option( 'woocommerce_tracker_last_send', false ) );
	}

	/**
	 * Get all the tracking data.
	 *
	 * @return array
	 */
	private static function get_tracking_data() {
		$data                       = array();

		// General site info.
		$data['url']                = home_url();
		$data['email']              = apply_filters( 'woocommerce_tracker_admin_email', get_option( 'admin_email' ) );
		$data['theme']              = self::get_theme_info();

		// WordPress Info.
		$data['wp']                 = self::get_wordpress_info();

		// Server Info.
		$data['server']             = self::get_server_info();

		// Plugin info.
		$all_plugins                = self::get_all_plugins();
		$data['active_plugins']     = $all_plugins['active_plugins'];
		$data['inactive_plugins']   = $all_plugins['inactive_plugins'];

		// Jetpack & WooCommerce Connect.
		$data['jetpack_version']    = defined( 'JETPACK__VERSION' ) ? JETPACK__VERSION : 'none';
		$data['jetpack_connected']  = ( class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_active' ) && Jetpack::is_active() ) ? 'yes' : 'no';
		$data['jetpack_is_staging'] = ( class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_staging_site' ) && Jetpack::is_staging_site() ) ? 'yes' : 'no';
		$data['connect_installed']  = class_exists( 'WC_Connect_Loader' ) ? 'yes' : 'no';
		$data['connect_active']     = ( class_exists( 'WC_Connect_Loader' ) && wp_next_scheduled( 'wc_connect_fetch_service_schemas' ) ) ? 'yes' : 'no';

		// Store count info.
		$data['users']              = self::get_user_counts();
		$data['products']           = self::get_product_counts();
		$data['orders']             = self::get_orders();

		// Payment gateway info.
		$data['gateways']           = self::get_active_payment_gateways();

		// Shipping method info.
		$data['shipping_methods']   = self::get_active_shipping_methods();

		// Get all WooCommerce options info.
		$data['settings']           = self::get_all_woocommerce_options_values();

		// Template overrides.
		$data['template_overrides'] = self::get_all_template_overrides();

		// Template overrides.
		$data['admin_user_agents']  = self::get_admin_user_agents();

		return apply_filters( 'woocommerce_tracker_data', $data );
	}

	/**
	 * Get the current theme info, theme name and version.
	 *
	 * @return array
	 */
	public static function get_theme_info() {
		$theme_data        = wp_get_theme();
		$theme_child_theme = wc_bool_to_string( is_child_theme() );
		$theme_wc_support  = wc_bool_to_string( current_theme_supports( 'woocommerce' ) );

		return array(
			'name'        => $theme_data->Name, // @phpcs:ignore
			'version'     => $theme_data->Version, // @phpcs:ignore
			'child_theme' => $theme_child_theme,
			'wc_support'  => $theme_wc_support,
		);
	}

	/**
	 * Get WordPress related data.
	 *
	 * @return array
	 */
	private static function get_wordpress_info() {
		$wp_data = array();

		$memory = wc_let_to_num( WP_MEMORY_LIMIT );

		if ( function_exists( 'memory_get_usage' ) ) {
			$system_memory = wc_let_to_num( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}

		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['version']      = get_bloginfo( 'version' );
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}

	/**
	 * Get server related info.
	 *
	 * @return array
	 */
	private static function get_server_info() {
		$server_data = array();

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE']; // @phpcs:ignore
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

		$database_version             = wc_get_server_database_version();
		$server_data['mysql_version'] = $database_version['number'];

		$server_data['php_max_upload_size'] = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap'] = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen'] = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl'] = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Get all plugins grouped into activated or not.
	 *
	 * @return array
	 */
	private static function get_all_plugins() {
		// Ensure get_plugins function is loaded.
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins             = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins      = array();

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
				// Remove active plugins from list so we can show active and inactive separately.
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}

		return array(
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => $plugins,
		);
	}

	/**
	 * Get user totals based on user role.
	 *
	 * @return array
	 */
	private static function get_user_counts() {
		$user_count          = array();
		$user_count_data     = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role.
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}

		return $user_count;
	}

	/**
	 * Get product totals based on product type.
	 *
	 * @return array
	 */
	private static function get_product_counts() {
		$product_count          = array();
		$product_count_data     = wp_count_posts( 'product' );
		$product_count['total'] = $product_count_data->publish;

		$product_statuses = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $product_statuses as $product_status ) {
			$product_count[ $product_status->name ] = $product_status->count;
		}

		return $product_count;
	}

	/**
	 * Get order counts
	 *
	 * @return array
	 */
	private static function get_order_counts() {
		$order_count      = array();
		$order_count_data = wp_count_posts( 'shop_order' );
		foreach ( wc_get_order_statuses() as $status_slug => $status_name ) {
			$order_count[ $status_slug ] = $order_count_data->{ $status_slug };
		}
		return $order_count;
	}

	/**
	 * Combine all order data.
	 *
	 * @return array
	 */
	private static function get_orders() {
		$order_dates  = self::get_order_dates();
		$order_counts = self::get_order_counts();
		$order_totals = self::get_order_totals();

		return array_merge( $order_dates, $order_counts, $order_totals );
	}

	/**
	 * Get a list of all active payment gateways.
	 *
	 * @return array
	 */
	private static function get_active_payment_gateways() {
		$active_gateways = array();
		$gateways        = WC()->payment_gateways->payment_gateways();
		foreach ( $gateways as $id => $gateway ) {
			if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
				$active_gateways[ $id ] = array(
					'title'    => $gateway->title,
					'supports' => $gateway->supports,
				);
			}
		}

		return $active_gateways;
	}

	/**
	 * Get a list of all active shipping methods.
	 *
	 * @return array
	 */
	private static function get_active_shipping_methods() {
		$active_methods   = array();
		$shipping_methods = WC()->shipping->get_shipping_methods();
		foreach ( $shipping_methods as $id => $shipping_method ) {
			if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {
				$active_methods[ $id ] = array(
					'title'      => $shipping_method->title,
					'tax_status' => $shipping_method->tax_status,
				);
			}
		}

		return $active_methods;
	}

	/**
	 * Get all options starting with woocommerce_ prefix.
	 *
	 * @return array
	 */
	private static function get_all_woocommerce_options_values() {
		return array(
			'version'                               => WC()->version,
			'currency'                              => get_woocommerce_currency(),
			'base_location'                         => WC()->countries->get_base_country(),
			'selling_locations'                     => WC()->countries->get_allowed_countries(),
			'api_enabled'                           => get_option( 'woocommerce_api_enabled' ),
			'weight_unit'                           => get_option( 'woocommerce_weight_unit' ),
			'dimension_unit'                        => get_option( 'woocommerce_dimension_unit' ),
			'download_method'                       => get_option( 'woocommerce_file_download_method' ),
			'download_require_login'                => get_option( 'woocommerce_downloads_require_login' ),
			'calc_taxes'                            => get_option( 'woocommerce_calc_taxes' ),
			'coupons_enabled'                       => get_option( 'woocommerce_enable_coupons' ),
			'guest_checkout'                        => get_option( 'woocommerce_enable_guest_checkout' ),
			'secure_checkout'                       => get_option( 'woocommerce_force_ssl_checkout' ),
			'enable_signup_and_login_from_checkout' => get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
			'enable_myaccount_registration'         => get_option( 'woocommerce_enable_myaccount_registration' ),
			'registration_generate_username'        => get_option( 'woocommerce_registration_generate_username' ),
			'registration_generate_password'        => get_option( 'woocommerce_registration_generate_password' ),
		);
	}

	/**
	 * Look for any template override and return filenames.
	 *
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
				} elseif ( file_exists( get_stylesheet_directory() . '/' . WC()->template_path() . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/' . WC()->template_path() . $file;
				} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
					$theme_file = get_template_directory() . '/' . $file;
				} elseif ( file_exists( get_template_directory() . '/' . WC()->template_path() . $file ) ) {
					$theme_file = get_template_directory() . '/' . WC()->template_path() . $file;
				} else {
					$theme_file = false;
				}

				if ( false !== $theme_file ) {
					$override_data[] = basename( $theme_file );
				}
			}
		}
		return $override_data;
	}

	/**
	 * When an admin user logs in, there user agent is tracked in user meta and collected here.
	 *
	 * @return array
	 */
	private static function get_admin_user_agents() {
		return array_filter( (array) get_option( 'woocommerce_tracker_ua', array() ) );
	}

	/**
	 * Get order totals
	 *
	 * @return array
	 */
	private static function get_order_totals() {
		global $wpdb;

		$gross_total = $wpdb->get_var( "
			SELECT
				SUM( order_meta.meta_value ) AS 'gross_total'
			FROM {$wpdb->prefix}posts AS orders
			LEFT JOIN {$wpdb->prefix}postmeta AS order_meta ON order_meta.post_id = orders.ID
			WHERE order_meta.meta_key =  '_order_total'
				AND orders.post_status =  'wc-completed'
			GROUP BY order_meta.meta_key
		" );

		if ( is_null( $gross_total ) ) {
			$gross_total = 0;
		}

		return array(
			'gross' => $gross_total,
		);
	}

	/**
	 * Get last order date
	 *
	 * @return string
	 */
	private static function get_order_dates() {
		global $wpdb;

		$min_max = $wpdb->get_row( "
			SELECT
				MIN( post_date_gmt ) as 'first', MAX( post_date_gmt ) as 'last'
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'shop_order'
			AND post_status = 'wc-completed'
		", ARRAY_A );

		if ( is_null( $min_max ) ) {
			$min_max = array(
				'first' => '-',
				'last'  => '-',
			);
		}

		return $min_max;
	}

	/**
	 * Make a request when opting out of tracker usage.
	 *
	 * @return void
	 */
	public static function opt_out_request() {
		$body = array(
			'event' => 'opt-out',
			'email' => apply_filters( 'woocommerce_tracker_admin_email', get_option( 'admin_email' ) ),
			'url'   => home_url(),
		);
		wp_safe_remote_post( self::$api_url . 'event/', array(
			'method'      => 'POST',
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false,
			'headers'     => array( 'user-agent' => 'WooCommerceTracker/' . md5( esc_url_raw( home_url( '/' ) ) ) . ';' ),
			'body'        => wp_json_encode( $body ),
			'cookies'     => array(),
		) );
	}
}

WC_Tracker::init();
