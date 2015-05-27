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
		add_action( 'before_woocommerce_init', array( __CLASS__, 'prevent_caching' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
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
	private static function delete_version_transients( $version ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s;", "\_transient\_%" . $version ) );
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
			$wc_page_uris[] = '/' . $page->post_name;
		}

		return $wc_page_uris;
	}

	/**
	 * Prevent caching on dynamic pages.
	 *
	 * @access public
	 * @return void
	 */
	public static function prevent_caching() {
		if ( false === ( $wc_page_uris = get_transient( 'woocommerce_cache_excluded_uris' ) ) ) {
			$wc_page_uris   = array_filter( array_merge( self::get_page_uris( 'cart' ), self::get_page_uris( 'checkout' ), self::get_page_uris( 'myaccount' ) ) );
	    	set_transient( 'woocommerce_cache_excluded_uris', $wc_page_uris );
		}

		if ( is_array( $wc_page_uris ) ) {
			foreach( $wc_page_uris as $uri ) {
				if ( strstr( $_SERVER['REQUEST_URI'], $uri ) ) {
					self::nocache();
					break;
				}
			}
		}
	}

	/**
	 * Set nocache constants and headers.
	 *
	 * @access private
	 * @return void
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
	 *
	 * @access public
	 * @return void
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
