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
	 * Hook in methods
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'geolocation_ajax_redirect' ) );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'prevent_caching' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'delete_version_transients', array( __CLASS__, 'delete_version_transients' ) );
	}

	/**
	 * Get a hash of the customer location
	 * @return string
	 */
	public static function geolocation_ajax_get_location_hash() {
		$customer             = new WC_Customer();
		$location             = array();
		$location['country']  = $customer->get_country();
		$location['state']    = $customer->get_state();
		$location['postcode'] = $customer->get_postcode();
		$location['city']     = $customer->get_city();
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
	 * Get transient version
	 *
	 * When using transients with unpredictable names, e.g. those containing an md5
	 * hash in the name, we need a way to invalidate them all at once.
	 *
	 * When using default WP transients we're able to do this with a DB query to
	 * delete transients manually.
	 *
	 * With external cache however, this isn't possible. Instead, this function is used
	 * to append a unique string (based on time()) to each transient. When transients
	 * are invalidated, the transient version will increment and data will be regenerated.
	 *
	 * Raised in issue https://github.com/woothemes/woocommerce/issues/5777
	 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/
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
	 * Get the page name/id for a WC page
	 * @param  string $wc_page
	 * @return array
	 */
	private static function get_page_uris( $wc_page ) {
		$wc_page_uris = array();

		if ( ( $page_id = wc_get_page_id( $wc_page ) ) && $page_id > 0 && ( $page = get_post( $page_id ) ) ) {
			$wc_page_uris[] = 'p=' . $page_id;
			$wc_page_uris[] = '/' . $page->post_name . '/';
		}

		return $wc_page_uris;
	}

	/**
	 * Prevent caching on dynamic pages.
	 * @access public
	 */
	public static function prevent_caching() {
		if ( false === ( $wc_page_uris = get_transient( 'woocommerce_cache_excluded_uris' ) ) ) {
			$wc_page_uris   = array_filter( array_merge( self::get_page_uris( 'cart' ), self::get_page_uris( 'checkout' ), self::get_page_uris( 'myaccount' ) ) );
	    	set_transient( 'woocommerce_cache_excluded_uris', $wc_page_uris );
		}

		if ( isset( $_GET['download_file'] ) ) {
			self::nocache();
		} elseif ( is_array( $wc_page_uris ) ) {
			foreach( $wc_page_uris as $uri ) {
				if ( stristr( $_SERVER['REQUEST_URI'], $uri ) ) {
					self::nocache();
					break;
				}
			}
		}
	}

	/**
	 * Set nocache constants and headers.
	 * @access private
	 */
	private static function nocache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) )
			define( "DONOTCACHEPAGE", "true" );

		if ( ! defined( 'DONOTCACHEOBJECT' ) )
			define( "DONOTCACHEOBJECT", "true" );

		if ( ! defined( 'DONOTCACHEDB' ) )
			define( "DONOTCACHEDB", "true" );

		nocache_headers();
	}

	/**
	 * notices function.
	 */
	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance('W3_Config');
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_wc_session_', $settings ) ) {
			?>
			<div class="error">
				<p><?php printf( __( 'In order for <strong>database caching</strong> to work with WooCommerce you must add <code>_wc_session_</code> to the "Ignored Query Strings" option in W3 Total Cache settings <a href="%s">here</a>.', 'woocommerce' ), admin_url( 'admin.php?page=w3tc_dbcache' ) ); ?></p>
			</div>
			<?php
		}
	}
}

WC_Cache_Helper::init();
