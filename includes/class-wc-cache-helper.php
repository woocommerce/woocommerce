<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Cache_Helper class.
 *
 * @class 		WC_Cache_Helper
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cache_Helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'geolocation_ajax_redirect' ) );
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'delete_version_transients', array( __CLASS__, 'delete_version_transients' ) );
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 * @param  string $group
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed
		$prefix = wp_cache_get( 'wc_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'wc_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'wc_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 * @param  string $group
	 */
	public static function incr_cache_prefix( $group ) {
		wp_cache_incr( 'wc_' . $group . '_cache_prefix', 1, $group );
	}

	/**
	 * Get a hash of the customer location.
	 * @return string
	 */
	public static function geolocation_ajax_get_location_hash() {
		$customer             = new WC_Customer( 0, true );
		$location             = array();
		$location['country']  = $customer->get_billing_country();
		$location['state']    = $customer->get_billing_state();
		$location['postcode'] = $customer->get_billing_postcode();
		$location['city']     = $customer->get_billing_city();
		return substr( md5( implode( '', $location ) ), 0, 12 );
	}

	/**
	 * When using geolocation via ajax, to bust cache, redirect if the location hash does not equal the querystring.
	 *
	 * This prevents caching of the wrong data for this request.
	 */
	public static function geolocation_ajax_redirect() {
		if ( 'geolocation_ajax' === get_option( 'woocommerce_default_customer_address' ) && ! is_checkout() && ! is_cart() && ! is_account_page() && ! is_ajax() && empty( $_POST ) ) {
			$location_hash = self::geolocation_ajax_get_location_hash();
			$current_hash  = isset( $_GET['v'] ) ? wc_clean( $_GET['v'] ) : '';
			if ( empty( $current_hash ) || $current_hash !== $location_hash ) {
				global $wp;

				$redirect_url = trailingslashit( home_url( $wp->request ) );

				if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
					$redirect_url = add_query_arg( $_SERVER['QUERY_STRING'], '', $redirect_url );
				}

				if ( ! get_option( 'permalink_structure' ) ) {
					$redirect_url = add_query_arg( $wp->query_string, '', $redirect_url );
				}

				$redirect_url = add_query_arg( 'v', $location_hash, remove_query_arg( 'v', $redirect_url ) );

				wp_safe_redirect( esc_url_raw( $redirect_url ), 307 );
				exit;
			}
		}
	}

	/**
	 * Get transient version.
	 *
	 * When using transients with unpredictable names, e.g. those containing an md5.
	 * hash in the name, we need a way to invalidate them all at once.
	 *
	 * When using default WP transients we're able to do this with a DB query to.
	 * delete transients manually.
	 *
	 * With external cache however, this isn't possible. Instead, this function is used.
	 * to append a unique string (based on time()) to each transient. When transients.
	 * are invalidated, the transient version will increment and data will be regenerated.
	 *
	 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
	 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
	 *
	 * @param  string  $group   Name for the group of transients we need to invalidate
	 * @param  boolean $refresh true to force a new version
	 * @return string transient version based on time(), 10 digits
	 */
	public static function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			self::delete_version_transients( $transient_value );
			set_transient( $transient_name, $transient_value = time() );
		}
		return $transient_value;
	}

	/**
	 * When the transient version increases, this is used to remove all past transients to avoid filling the DB.
	 *
	 * Note; this only works on transients appended with the transient version, and when object caching is not being used.
	 *
	 * @since  2.3.10
	 *
	 * @param string $version
	 */
	public static function delete_version_transients( $version = '' ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			global $wpdb;

			$limit    = apply_filters( 'woocommerce_delete_version_transients_limit', 1000 );
			$affected = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id LIMIT %d;", "\_transient\_%" . $version, $limit ) );

			// If affected rows is equal to limit, there are more rows to delete. Delete in 10 secs.
			if ( $affected === $limit ) {
				wp_schedule_single_event( time() + 10, 'delete_version_transients', array( $version ) );
			}
		}
	}

	/**
	 * Prevent caching on dynamic pages.
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}
		$page_ids        = array_filter( array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ), wc_get_page_id( 'myaccount' ) ) );
		$current_page_id = get_queried_object_id();

		if ( isset( $_GET['download_file'] ) || in_array( $current_page_id, $page_ids ) ) {
			self::nocache();
		}
	}

	/**
	 * Set nocache constants and headers.
	 * @access private
	 */
	private static function nocache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( "DONOTCACHEPAGE", true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( "DONOTCACHEOBJECT", true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( "DONOTCACHEDB", true );
		}
		nocache_headers();
	}

	/**
	 * notices function.
	 */
	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance( 'W3_Config' );
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_wc_session_', $settings ) ) {
			?>
			<div class="error">
				<p><?php printf( __( 'In order for <strong>database caching</strong> to work with WooCommerce you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', 'woocommerce' ), '<code>_wc_session_</code>', admin_url( 'admin.php?page=w3tc_dbcache' ) ); ?></p>
			</div>
			<?php
		}
	}
}

WC_Cache_Helper::init();
