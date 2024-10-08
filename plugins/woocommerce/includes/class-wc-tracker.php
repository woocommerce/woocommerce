<?php
/**
 * WooCommerce Tracker
 *
 * The WooCommerce tracker class adds functionality to track WooCommerce usage based on if the customer opted in.
 * No personal information is tracked, only general WooCommerce settings, general product, order and user counts and admin email for discount code.
 *
 * @class WC_Tracker
 * @since 2.3.0
 * @package WooCommerce\Classes
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\{ FeaturesUtil, OrderUtil, PluginUtil };
use Automattic\WooCommerce\Internal\Utilities\BlocksUtil;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backwards compatibility.
/**
 * WooCommerce Tracker Class
 */
class WC_Tracker {

	// phpcs:enable
	/**
	 * URL to the WooThemes Tracker API endpoint.
	 *
	 * @var string
	 */
	private static $api_url = 'https://tracking.woocommerce.com/v1/';

	/**
	 * Hook into cron event.
	 */
	public static function init() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingFinal, WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag -- Not an injection.
		add_action( 'woocommerce_tracker_send_event', array( __CLASS__, 'send_tracking_data' ) );
	}

	/**
	 * Decide whether to send tracking data or not.
	 *
	 * @param boolean $override Should override?.
	 */
	public static function send_tracking_data( $override = false ) {
		// Don't trigger this on AJAX Requests.
		if ( Constants::is_true( 'DOING_AJAX' ) ) {
			return;
		}

		/**
		 * Filter whether to send tracking data or not.
		 *
		 * @since 2.3.0
		 */
		if ( ! apply_filters( 'woocommerce_tracker_send_override', $override ) ) {
			// Send a maximum of once per week by default.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'woocommerce_tracker_last_send_interval', strtotime( '-1 week' ) ) ) { // phpcs:ignore
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
		wp_safe_remote_post(
			self::$api_url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => false,
				'headers'     => array( 'user-agent' => 'WooCommerceTracker/' . md5( esc_url_raw( home_url( '/' ) ) ) . ';' ),
				'body'        => wp_json_encode( $params ),
				'cookies'     => array(),
			)
		);
	}

	/**
	 * Get the last time tracking data was sent.
	 *
	 * @return int|bool
	 */
	private static function get_last_send_time() {
		/**
		 * Filter the last time tracking data was sent.
		 *
		 * @since 2.3.0
		 */
		return apply_filters( 'woocommerce_tracker_last_send_time', get_option( 'woocommerce_tracker_last_send', false ) );
	}

	/**
	 * Test whether this site is a staging site according to the Jetpack criteria.
	 *
	 * With Jetpack 8.1+, Jetpack::is_staging_site has been deprecated.
	 * \Automattic\Jetpack\Status::is_staging_site is the replacement.
	 * However, there are version of JP where \Automattic\Jetpack\Status exists, but does *not* contain is_staging_site method,
	 * so with those, code still needs to use the previous check as a fallback.
	 *
	 * After upgrading Jetpack Status to v3.3.2 is_staging_site is also deprecated and in_safe_mode is the new replacement.
	 * So we check this first of all.
	 *
	 * @return bool
	 */
	private static function is_jetpack_staging_site() {
		if ( class_exists( '\Automattic\Jetpack\Status' ) ) {

			$jp_status = new \Automattic\Jetpack\Status();

			if ( is_callable( array( $jp_status, 'in_safe_mode' ) ) ) {
				return $jp_status->in_safe_mode();
			} elseif ( is_callable( array( $jp_status, 'is_staging_site' ) ) ) {
				// Preferred way of checking with Jetpack 8.1+.
				return $jp_status->is_staging_site();
			}
		}

		return ( class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_staging_site' ) && Jetpack::is_staging_site() );
	}

	/**
	 * Get all the tracking data.
	 *
	 * @return array
	 */
	public static function get_tracking_data() {
		$data = array();

		// General site info.
		$data['url']      = home_url();
		$data['store_id'] = get_option( \WC_Install::STORE_ID_OPTION, null );
		$data['blog_id']  = class_exists( 'Jetpack_Options' ) ? Jetpack_Options::get_option( 'id' ) : null;

		/**
		 * Filter the admin email that's sent with data.
		 *
		 * @since 2.3.0
		 */
		$data['email'] = apply_filters( 'woocommerce_tracker_admin_email', get_option( 'admin_email' ) );
		$data['theme'] = self::get_theme_info();

		// WordPress Info.
		$data['wp'] = self::get_wordpress_info();

		// Server Info.
		$data['server'] = self::get_server_info();

		// Plugin info.
		$all_plugins              = self::get_all_plugins();
		$data['active_plugins']   = $all_plugins['active_plugins'];
		$data['inactive_plugins'] = $all_plugins['inactive_plugins'];

		// Jetpack & WooCommerce Connect.
		$data['jetpack_version']    = Constants::is_defined( 'JETPACK__VERSION' ) ? Constants::get_constant( 'JETPACK__VERSION' ) : 'none';
		$data['jetpack_connected']  = ( class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_active' ) && Jetpack::is_active() ) ? 'yes' : 'no';
		$data['jetpack_is_staging'] = self::is_jetpack_staging_site() ? 'yes' : 'no';
		$data['connect_installed']  = class_exists( 'WC_Connect_Loader' ) ? 'yes' : 'no';
		$data['connect_active']     = ( class_exists( 'WC_Connect_Loader' ) && wp_next_scheduled( 'wc_connect_fetch_service_schemas' ) ) ? 'yes' : 'no';
		$data['helper_connected']   = self::get_helper_connected();

		// Store count info.
		$data['users']      = self::get_user_counts();
		$data['products']   = self::get_product_counts();
		$data['orders']     = self::get_orders();
		$data['reviews']    = self::get_review_counts();
		$data['categories'] = self::get_category_counts();

		// Payment gateway info.
		$data['gateways'] = self::get_active_payment_gateways();

		// WcPay settings info.
		$data['wcpay_settings'] = self::get_wcpay_settings();

		// Shipping method info.
		$data['shipping_methods'] = self::get_active_shipping_methods();

		// Features.
		$data['enabled_features'] = self::get_enabled_features();

		// Get all WooCommerce options info.
		$data['settings'] = self::get_all_woocommerce_options_values();

		// Template overrides.
		$data['template_overrides'] = self::get_all_template_overrides();

		// Cart & checkout tech (blocks or shortcodes).
		$data['cart_checkout'] = self::get_cart_checkout_info();

		// Mini Cart block, which only exists since wp 5.9.
		if ( version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) ) {
			$data['mini_cart_block'] = self::get_mini_cart_info();
		}

		/**
		 * Filter whether to disable admin tracking.
		 *
		 * @since 5.2.0
		 */
		$data['wc_admin_disabled'] = apply_filters( 'woocommerce_admin_disabled', false ) ? 'yes' : 'no';

		// Mobile info.
		$data['wc_mobile_usage'] = self::get_woocommerce_mobile_usage();

		/**
		 * Filter the data that's sent with the tracker.
		 *
		 * @since 2.3.0
		 */
		return apply_filters( 'woocommerce_tracker_data', $data );
	}

	/**
	 * Get the current theme info, theme name and version.
	 *
	 * @return array
	 */
	public static function get_theme_info() {
		$theme_data           = wp_get_theme();
		$theme_child_theme    = wc_bool_to_string( is_child_theme() );
		$theme_wc_support     = wc_bool_to_string( current_theme_supports( 'woocommerce' ) );
		$theme_is_block_theme = wc_bool_to_string( wc_current_theme_is_fse_theme() );

		return array(
			'name'        => $theme_data->Name, // @phpcs:ignore
			'version'     => $theme_data->Version, // @phpcs:ignore
			'child_theme' => $theme_child_theme,
			'wc_support'  => $theme_wc_support,
			'block_theme' => $theme_is_block_theme,
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
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- False positive.
			$system_memory = wc_let_to_num( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}

		// WordPress 5.5+ environment type specification.
		// 'production' is the default in WP, thus using it as a default here, too.
		$environment_type = 'production';
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$environment_type = wp_get_environment_type();
		}

		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['version']      = get_bloginfo( 'version' );
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';
		$wp_data['env_type']     = $environment_type;
		$wp_data['dropins']      = array_keys( get_dropins() );

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
			$server_data['php_post_max_size']  = size_format( wc_let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt']      = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin']        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		$database_version             = wc_get_server_database_version();
		$server_data['mysql_version'] = $database_version['number'];

		$server_data['php_max_upload_size']  = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap']             = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen']        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl']             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

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

		$plugins             = wc_get_container()->get( LegacyProxy::class )->call_function( 'get_plugins' );
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins      = array();

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted         = array();
			$formatted['name'] = wp_strip_all_tags( $v['Name'] );
			if ( isset( $v['Version'] ) ) {
				$formatted['version'] = wp_strip_all_tags( $v['Version'] );
			}
			if ( isset( $v['Author'] ) ) {
				$formatted['author'] = wp_strip_all_tags( $v['Author'] );
			}
			if ( isset( $v['Network'] ) ) {
				$formatted['network'] = wp_strip_all_tags( $v['Network'] );
			}
			if ( isset( $v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = wp_strip_all_tags( $v['PluginURI'] );
			}
			$formatted['feature_compatibility'] = array();
			if ( wc_get_container()->get( PluginUtil::class )->is_woocommerce_aware_plugin( $k ) ) {
				$formatted['feature_compatibility'] = array_filter( FeaturesUtil::get_compatible_features_for_plugin( $k ) );
			}
			if ( in_array( $k, $active_plugins_keys, true ) ) {
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
	 * Get the settings of WooCommerce Payments plugin
	 *
	 * @return array
	 */
	private static function get_wcpay_settings() {
		return get_option( 'woocommerce_woocommerce_payments_settings' );
	}

	/**
	 * Check to see if the helper is connected to WooCommerce.com
	 *
	 * @return string
	 */
	private static function get_helper_connected() {
		if ( class_exists( 'WC_Helper_Options' ) && is_callable( 'WC_Helper_Options::get' ) ) {
			$authenticated = WC_Helper_Options::get( 'auth' );
		} else {
			$authenticated = '';
		}
		return ( ! empty( $authenticated ) ) ? 'yes' : 'no';
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
	public static function get_product_counts() {
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
	 * Get order counts.
	 *
	 * @return array
	 */
	private static function get_order_counts() {
		$order_count = array();
		foreach ( wc_get_order_statuses() as $status_slug => $status_name ) {
			$order_count[ $status_slug ] = wc_orders_count( $status_slug );
		}
		return $order_count;
	}

	/**
	 * Combine all order data.
	 *
	 * @return array
	 */
	private static function get_orders() {
		$order_dates    = self::get_order_dates();
		$order_counts   = self::get_order_counts();
		$order_totals   = self::get_order_totals();
		$order_gateways = self::get_orders_by_gateway();
		$order_origin   = self::get_orders_origins();

		return array_merge( $order_dates, $order_counts, $order_totals, $order_gateways, $order_origin );
	}

	/**
	 * Get order totals.
	 *
	 * @since 5.4.0
	 * @return array
	 */
	private static function get_order_totals() {
		global $wpdb;

		$orders_table = OrdersTableDataStore::get_orders_table_name();

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$gross_total = $wpdb->get_var(
				"
				SELECT SUM(total_amount) AS 'gross_total'
				FROM $orders_table
				WHERE status in ('wc-completed', 'wc-refunded');
			"
			);
			// phpcs:enable
		} else {
			$gross_total = $wpdb->get_var(
				"
					SELECT
						SUM( order_meta.meta_value ) AS 'gross_total'
					FROM {$wpdb->prefix}posts AS orders
					LEFT JOIN {$wpdb->prefix}postmeta AS order_meta ON order_meta.post_id = orders.ID
					WHERE order_meta.meta_key = '_order_total'
						AND orders.post_status in ( 'wc-completed', 'wc-refunded' )
					GROUP BY order_meta.meta_key
				"
			);
		}

		if ( is_null( $gross_total ) ) {
			$gross_total = 0;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$processing_gross_total = $wpdb->get_var(
				"
				SELECT SUM(total_amount) AS 'gross_total'
				FROM $orders_table
				WHERE status = 'wc-processing';
			"
			);
			// phpcs:enable
		} else {
			$processing_gross_total = $wpdb->get_var(
				"
				SELECT
					SUM( order_meta.meta_value ) AS 'gross_total'
				FROM {$wpdb->prefix}posts AS orders
				LEFT JOIN {$wpdb->prefix}postmeta AS order_meta ON order_meta.post_id = orders.ID
				WHERE order_meta.meta_key = '_order_total'
					AND orders.post_status = 'wc-processing'
				GROUP BY order_meta.meta_key
			"
			);
		}

		if ( is_null( $processing_gross_total ) ) {
			$processing_gross_total = 0;
		}

		return array(
			'gross'            => $gross_total,
			'processing_gross' => $processing_gross_total,
		);
	}

	/**
	 * Get last order date.
	 *
	 * @return string
	 */
	private static function get_order_dates() {
		global $wpdb;

		$orders_table = OrdersTableDataStore::get_orders_table_name();
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$min_max = $wpdb->get_row(
				"
				SELECT
					MIN( date_created_gmt ) as 'first', MAX( date_created_gmt ) as 'last'
				FROM $orders_table
				WHERE status = 'wc-completed';
				",
				ARRAY_A
			);
			// phpcs:enable
		} else {
			$min_max = $wpdb->get_row(
				"
					SELECT
						MIN( post_date_gmt ) as 'first', MAX( post_date_gmt ) as 'last'
					FROM {$wpdb->prefix}posts
					WHERE post_type = 'shop_order'
					AND post_status = 'wc-completed'
				",
				ARRAY_A
			);
		}

		if ( is_null( $min_max ) ) {
			$min_max = array(
				'first' => '-',
				'last'  => '-',
			);
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$processing_min_max = $wpdb->get_row(
				"
				SELECT
					MIN( date_created_gmt ) as 'processing_first', MAX( date_created_gmt ) as 'processing_last'
				FROM $orders_table
				WHERE status = 'wc-processing';
				",
				ARRAY_A
			);
			// phpcs:enable
		} else {
			$processing_min_max = $wpdb->get_row(
				"
				SELECT
					MIN( post_date_gmt ) as 'processing_first', MAX( post_date_gmt ) as 'processing_last'
				FROM {$wpdb->prefix}posts
				WHERE post_type = 'shop_order'
				AND post_status = 'wc-processing'
			",
				ARRAY_A
			);
		}

		if ( is_null( $processing_min_max ) ) {
			$processing_min_max = array(
				'processing_first' => '-',
				'processing_last'  => '-',
			);
		}

		return array_merge( $min_max, $processing_min_max );
	}

	/**
	 * Extract the group key for an associative array of objects which have unique ids in the key.
	 * A 'group_key' property is introduced in the object.
	 * For example, two objects with keys like 'WooDataPay ** #123' and 'WooDataPay ** #78' would
	 * both have a group_key of 'WooDataPay **' after this function call.
	 *
	 * @param array  $objects     The array of objects that need to be grouped.
	 * @param string $default_key The property that will be the default group_key.
	 * @return array Contains the objects with a group_key property.
	 */
	private static function extract_group_key( $objects, $default_key ) {
		$keys = array_keys( $objects );

		// Sort keys by length and then by characters within the same length keys.
		usort(
			$keys,
			function( $a, $b ) {
				if ( strlen( $a ) === strlen( $b ) ) {
					return strcmp( $a, $b );
				}
				return ( strlen( $a ) < strlen( $b ) ) ? -1 : 1;
			}
		);

		// Look for common tokens in every pair of adjacent keys.
		$prev = '';
		foreach ( $keys as $key ) {
			if ( $prev ) {
				$comm_tokens = array();

				// Tokenize the current and previous gateway names.
				$curr_tokens = preg_split( '/[ :,\-_]+/', $key );
				$prev_tokens = preg_split( '/[ :,\-_]+/', $prev );

				$len_curr = is_array( $curr_tokens ) ? count( $curr_tokens ) : 0;
				$len_prev = is_array( $prev_tokens ) ? count( $prev_tokens ) : 0;

				$index_unique = -1;
				// Gather the common tokens.
				// Let us allow for the unique reference id to be anywhere in the name.
				for ( $i = 0; $i < $len_curr && $i < $len_prev; $i++ ) {
					if ( $curr_tokens[ $i ] === $prev_tokens[ $i ] ) {
						$comm_tokens[] = $curr_tokens[ $i ];
					} elseif ( preg_match( '/\d/', $curr_tokens[ $i ] ) && preg_match( '/\d/', $prev_tokens[ $i ] ) ) {
						$index_unique = $i;
					}
				}

				// If only one token is different, and those tokens contain digits, then that could be the unique id.
				if ( $len_curr - count( $comm_tokens ) <= 1 && count( $comm_tokens ) > 0 && $index_unique > -1 ) {
					$objects[ $key ]->group_key  = implode( ' ', $comm_tokens );
					$objects[ $prev ]->group_key = implode( ' ', $comm_tokens );
				} else {
					$objects[ $key ]->group_key = $objects[ $key ]->$default_key;
				}
			} else {
				$objects[ $key ]->group_key = $objects[ $key ]->$default_key;
			}
			$prev = $key;
		}
		return $objects;
	}

	/**
	 * Get order details by gateway.
	 *
	 * @return array
	 */
	private static function get_orders_by_gateway() {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$orders_table = OrdersTableDataStore::get_orders_table_name();
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$orders_and_gateway_details = $wpdb->get_results(
				"
				SELECT payment_method AS gateway, currency AS currency, SUM( total_amount ) AS totals, count( id ) AS counts
				FROM $orders_table
				WHERE status IN ( 'wc-completed', 'wc-processing', 'wc-refunded' )
				GROUP BY gateway, currency;
				"
			);
			// phpcs:enable
		} else {
			$orders_and_gateway_details = $wpdb->get_results(
				"
				SELECT
					gateway, currency, SUM(total) AS totals, COUNT(order_id) AS counts
				FROM (
					SELECT
						orders.id AS order_id,
						MAX(CASE WHEN meta_key = '_payment_method' THEN meta_value END) gateway,
						MAX(CASE WHEN meta_key = '_order_total' THEN meta_value END) total,
						MAX(CASE WHEN meta_key = '_order_currency' THEN meta_value END) currency
					FROM
						{$wpdb->prefix}posts orders
					LEFT JOIN
						{$wpdb->prefix}postmeta order_meta ON order_meta.post_id = orders.id
					WHERE orders.post_type = 'shop_order'
						AND orders.post_status in ( 'wc-completed', 'wc-processing', 'wc-refunded' )
						AND meta_key in( '_payment_method','_order_total','_order_currency')
					GROUP BY orders.id
				) order_gateways
				GROUP BY gateway, currency
				"
			);
		}

		$orders_by_gateway_currency = array();

		// The associative array that is created as the result of array_reduce is passed to extract_group_key()
		// This function has the logic that will remove specific transaction identifiers that may sometimes be part of a
		// payment method. For example, two payments methods like 'WooDataPay ** #123' and 'WooDataPay ** #78' would
		// both have the same group_key 'WooDataPay **'.
		$orders_by_gateway = self::extract_group_key(
			// Convert into an associative array with a combination of currency and gateway as key.
			array_reduce(
				$orders_and_gateway_details,
				function( $result, $item ) {
					$item->gateway = preg_replace( '/\s+/', ' ', $item->gateway );

					// Introduce currency as a prefix for the key.
					$key = $item->currency . '==' . $item->gateway;

					$result[ $key ] = $item;
					return $result;
				},
				array()
			),
			'gateway'
		);

		// Aggregate using group_key.
		foreach ( $orders_by_gateway as $orders_details ) {
			$gkey = $orders_details->group_key;

			// Remove currency as prefix of key for backward compatibility.
			if ( str_contains( $gkey, '==' ) ) {
				$tokens = preg_split( '/==/', $gkey );
				$key    = $tokens[1];
			} else {
				$key = $gkey;
			}

			$key = str_replace( array( 'payment method', 'payment gateway', 'gateway' ), '', strtolower( $key ) );
			$key = trim( preg_replace( '/[: ,#*\-_]+/', ' ', $key ) );

			// Add currency as postfix of gateway for backward compatibility.
			$key       = 'gateway_' . $key . '_' . $orders_details->currency;
			$count_key = $key . '_count';
			$total_key = $key . '_total';

			if ( array_key_exists( $count_key, $orders_by_gateway_currency ) || array_key_exists( $total_key, $orders_by_gateway_currency ) ) {
				$orders_by_gateway_currency[ $count_key ] = $orders_by_gateway_currency[ $count_key ] + $orders_details->counts;
				$orders_by_gateway_currency[ $total_key ] = $orders_by_gateway_currency[ $total_key ] + $orders_details->totals;
			} else {
				$orders_by_gateway_currency[ $count_key ] = $orders_details->counts;
				$orders_by_gateway_currency[ $total_key ] = $orders_details->totals;
			}
		}

		return $orders_by_gateway_currency;
	}

	/**
	 * Get orders origin details.
	 *
	 * @return array
	 */
	private static function get_orders_origins() {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$op_table_name = OrdersTableDataStore::get_operational_data_table_name();
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$orders_origin = $wpdb->get_results(
				"
				SELECT created_via as origin, COUNT( order_id ) as count
				FROM $op_table_name
				GROUP BY created_via;
				"
			);
			// phpcs:enable
		} else {
			$orders_origin = $wpdb->get_results(
				"
				SELECT
					meta_value as origin, COUNT( DISTINCT ( orders.id ) ) as count
				FROM
					$wpdb->posts orders
				LEFT JOIN
					$wpdb->postmeta order_meta ON order_meta.post_id = orders.id
				WHERE
					meta_key = '_created_via'
				GROUP BY
					meta_value;
			"
			);
		}

		// The associative array that is created as the result of array_reduce is passed to extract_group_key()
		// This function has the logic that will remove specific identifiers that may sometimes be part of an origin.
		// For example, two origins like 'Import #123' and 'Import ** #78' would both have a group_key 'Import **'.
		$orders_and_origins = self::extract_group_key(
			// Convert into an associative array with the origin as key.
			array_reduce(
				$orders_origin,
				function( $result, $item ) {
					$key = $item->origin;

					$result[ $key ] = $item;
					return $result;
				},
				array()
			),
			'origin'
		);

		$orders_by_origin = array();

		// Aggregate using group_key.
		foreach ( $orders_and_origins as $origin ) {
			$key = strtolower( $origin->group_key );

			if ( array_key_exists( $key, $orders_by_origin ) ) {
				$orders_by_origin[ $key ] = $orders_by_origin[ $key ] + (int) $origin->count;
			} else {
				$orders_by_origin[ $key ] = (int) $origin->count;
			}
		}

		return array( 'created_via' => $orders_by_origin );
	}

	/**
	 * Get review counts for different statuses.
	 *
	 * @return array
	 */
	private static function get_review_counts() {
		global $wpdb;
		$review_count = array( 'total' => 0 );
		$status_map   = array(
			'0'     => 'pending',
			'1'     => 'approved',
			'trash' => 'trash',
			'spam'  => 'spam',
		);
		$counts       = $wpdb->get_results(
			"
			SELECT comment_approved, COUNT(*) AS num_reviews
			FROM {$wpdb->comments}
			WHERE comment_type = 'review'
			GROUP BY comment_approved
			",
			ARRAY_A
		);

		if ( ! $counts ) {
			return $review_count;
		}

		foreach ( $counts as $count ) {
			$status = $count['comment_approved'];
			if ( array_key_exists( $status, $status_map ) ) {
				$review_count[ $status_map[ $status ] ] = $count['num_reviews'];
			}
			$review_count['total'] += $count['num_reviews'];
		}

		return $review_count;
	}

	/**
	 * Get the number of product categories.
	 *
	 * @return int
	 */
	private static function get_category_counts() {
		return wp_count_terms( 'product_cat' );
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
		$shipping_methods = WC()->shipping()->get_shipping_methods();
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
	 * Get an array of slugs for WC features that are enabled on the site.
	 *
	 * @return string[]
	 */
	private static function get_enabled_features() {
		$all_features     = FeaturesUtil::get_features( true, true );
		$enabled_features = array_filter(
			$all_features,
			function( $feature ) {
				return $feature['is_enabled'];
			}
		);

		return array_keys( $enabled_features );
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
			'base_state'                            => WC()->countries->get_base_state(),
			'base_postcode'                         => WC()->countries->get_base_postcode(),
			'selling_locations'                     => WC()->countries->get_allowed_countries(),
			'api_enabled'                           => get_option( 'woocommerce_api_enabled', 'no' ),
			'weight_unit'                           => get_option( 'woocommerce_weight_unit' ),
			'dimension_unit'                        => get_option( 'woocommerce_dimension_unit' ),
			'download_method'                       => get_option( 'woocommerce_file_download_method' ),
			'download_require_login'                => get_option( 'woocommerce_downloads_require_login' ),
			'calc_taxes'                            => get_option( 'woocommerce_calc_taxes' ),
			'coupons_enabled'                       => get_option( 'woocommerce_enable_coupons' ),
			'guest_checkout'                        => get_option( 'woocommerce_enable_guest_checkout' ),
			'checkout_login_reminder'               => get_option( 'woocommerce_enable_checkout_login_reminder' ),
			'secure_checkout'                       => get_option( 'woocommerce_force_ssl_checkout' ),
			'enable_signup_and_login_from_checkout' => get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
			'enable_myaccount_registration'         => get_option( 'woocommerce_enable_myaccount_registration' ),
			'registration_generate_username'        => get_option( 'woocommerce_registration_generate_username' ),
			'registration_generate_password'        => get_option( 'woocommerce_registration_generate_password' ),
			'hpos_sync_enabled'                     => get_option( 'woocommerce_custom_orders_table_data_sync_enabled' ),
			'hpos_cot_authoritative'                => get_option( 'woocommerce_custom_orders_table_enabled' ),
			'hpos_transactions_enabled'             => get_option( 'woocommerce_use_db_transactions_for_custom_orders_table_data_sync' ),
			'hpos_transactions_level'               => get_option( 'woocommerce_db_transactions_isolation_level_for_custom_orders_table_data_sync' ),
			'show_marketplace_suggestions'          => get_option( 'woocommerce_show_marketplace_suggestions' ),
			'admin_install_timestamp'               => get_option( 'woocommerce_admin_install_timestamp' ),
		);
	}

	/**
	 * Look for any template override and return filenames.
	 *
	 * @return array
	 */
	private static function get_all_template_overrides() {
		$override_data = array();
		/**
		 * Filter the paths to scan for template overrides.
		 *
		 * @since 2.3.0
		 */
		$template_paths = apply_filters( 'woocommerce_template_overrides_scan_paths', array( 'WooCommerce' => WC()->plugin_path() . '/templates/' ) );
		$scanned_files  = array();

		require_once WC()->plugin_path() . '/includes/admin/class-wc-admin-status.php';

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
	 * Search a specific post for text content.
	 *
	 * @param integer $post_id The id of the post to search.
	 * @param string  $text    The text to search for.
	 * @return string 'Yes' if post contains $text (otherwise 'No').
	 */
	public static function post_contains_text( $post_id, $text ) {
		global $wpdb;

		// Search for the text anywhere in the post.
		$wildcarded = "%{$text}%";

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT COUNT( * ) FROM {$wpdb->prefix}posts
				WHERE ID=%d
				AND {$wpdb->prefix}posts.post_content LIKE %s
				",
				array( $post_id, $wildcarded )
			)
		);

		return ( '0' !== $result ) ? 'Yes' : 'No';
	}


	/**
	 * Get tracker data for a specific block type on a woocommerce page.
	 *
	 * @param string $block_name The name (id) of a block, e.g. `woocommerce/cart`.
	 * @param string $woo_page_name The woo page to search, e.g. `cart`.
	 * @return array Associative array of tracker data with keys:
	 * - page_contains_block
	 * - block_attributes
	 */
	public static function get_block_tracker_data( $block_name, $woo_page_name ) {
		$blocks = WC_Blocks_Utils::get_blocks_from_page( $block_name, $woo_page_name );

		$block_present = false;
		$attributes    = array();
		if ( $blocks && count( $blocks ) ) {
			// Return any customised attributes from the first block.
			$block_present = true;
			$attributes    = $blocks[0]['attrs'];
		}

		return array(
			'page_contains_block' => $block_present ? 'Yes' : 'No',
			'block_attributes'    => $attributes,
		);
	}

	/**
	 * Get tracker data for a pickup location method.
	 *
	 * @return array Associative array of tracker data with keys:
	 * - pickup_location_enabled
	 * - pickup_locations_count
	 */
	public static function get_pickup_location_data() {
		$pickup_location_enabled          = false;
		$pickup_location_pickup_locations = get_option( 'pickup_location_pickup_locations', array() );
		$pickup_locations_count           = is_countable( $pickup_location_pickup_locations ) ? count( $pickup_location_pickup_locations ) : 0;

		// Get the available shipping methods.
		$shipping_methods = WC()->shipping()->get_shipping_methods();

		// Check if the desired shipping method is enabled.
		if ( isset( $shipping_methods['pickup_location'] ) && $shipping_methods['pickup_location']->is_enabled() ) {
			$pickup_location_enabled = true;
		}

		return array(
			'pickup_location_enabled' => $pickup_location_enabled,
			'pickup_locations_count'  => $pickup_locations_count,
		);
	}

	/**
	 * Get tracker data for additional fields on the checkout page.
	 *
	 * @return array Array of fields count and names.
	 */
	public static function get_checkout_additional_fields_data() {
		$additional_fields_controller = Package::container()->get( CheckoutFields::class );

		return array(
			'fields_count' => count( $additional_fields_controller->get_additional_fields() ),
			'fields_names' => array_keys( $additional_fields_controller->get_additional_fields() ),
		);
	}
	/**
	 * Get info about the cart & checkout pages.
	 *
	 * @return array
	 */
	public static function get_cart_checkout_info() {
		$cart_page_id     = wc_get_page_id( 'cart' );
		$checkout_page_id = wc_get_page_id( 'checkout' );

		$cart_block_data     = self::get_block_tracker_data( 'woocommerce/cart', 'cart' );
		$checkout_block_data = self::get_block_tracker_data( 'woocommerce/checkout', 'checkout' );

		$pickup_location_data = self::get_pickup_location_data();

		$additional_fields_data = self::get_checkout_additional_fields_data();

		return array(
			'cart_page_contains_cart_shortcode'         => self::post_contains_text(
				$cart_page_id,
				'[woocommerce_cart]'
			),
			'checkout_page_contains_checkout_shortcode' => self::post_contains_text(
				$checkout_page_id,
				'[woocommerce_checkout]'
			),

			'cart_page_contains_cart_block'             => $cart_block_data['page_contains_block'],
			'cart_block_attributes'                     => $cart_block_data['block_attributes'],
			'checkout_page_contains_checkout_block'     => $checkout_block_data['page_contains_block'],
			'checkout_block_attributes'                 => $checkout_block_data['block_attributes'],
			'pickup_location'                           => $pickup_location_data,
			'additional_fields'                         => $additional_fields_data,
		);
	}

	/**
	 * Get info about the Mini Cart Block.
	 *
	 * @return array
	 */
	private static function get_mini_cart_info() {
		$mini_cart_block_name = 'woocommerce/mini-cart';
		$mini_cart_block_data = wc_current_theme_is_fse_theme() ? BlocksUtil::get_block_from_template_part( $mini_cart_block_name, 'header' ) : BlocksUtil::get_blocks_from_widget_area( $mini_cart_block_name );
		return array(
			'mini_cart_used'             => empty( $mini_cart_block_data[0] ) ? 'No' : 'Yes',
			'mini_cart_block_attributes' => empty( $mini_cart_block_data[0] ) ? array() : $mini_cart_block_data[0]['attrs'],
		);
	}

	/**
	 * Get info about WooCommerce Mobile App usage
	 *
	 * @return array
	 */
	public static function get_woocommerce_mobile_usage() {
		return get_option( 'woocommerce_mobile_app_usage' );
	}
}
