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
        add_action( 'woocommerce_loaded', array( __CLASS__, 'define_caching_constants' ) );
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
			$transient_value = time();
			set_transient( $transient_name, $transient_value );
		}
		return $transient_value;
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

    /**
     * Define WC caching expires to deal with object cache quirks
     *
     * @access public
     * @return void
     */
    public static function define_caching_constants() {

        if ( !defined( 'WC_EXPIRE_YEAR_IN_SECONDS' ) ) {

            $expire_year = self::is_memcached_patch_needed() ? time() + YEAR_IN_SECONDS : YEAR_IN_SECONDS;

            define( 'WC_EXPIRE_YEAR_IN_SECONDS', $expire_year );

        }

    }

    /**
     * Check if Memcached 'expire' patch is needed
     *
     * @global WP_Object_Cache $wp_object_cache
     * @access private
     * @return boolean
     */
    private static function is_memcached_patch_needed() {

        global $wp_object_cache;

        // Are we on Memcached?
        if ( !wp_using_ext_object_cache() || !isset( $wp_object_cache->mc ) )
            return false;

        $group = 'woocommerce';
        $key   = 'is_memcached_ok';

        // Did we run this test before and pass?
        if ( 'yes' === wp_cache_get( $key, $group ) )
            return false;

        // Attempt to set a value a year in the future
        wp_cache_set( $key, 'yes', $group, YEAR_IN_SECONDS );

        // Get the Memcache(d) object
        $mc = $wp_object_cache->get_mc( $group );

        //Just making sure that we are dealing with Memcached
        if ( !is_a( $mc, 'Memcache' ) && !is_a( $mc, 'Memcached' ) )
            return false;

        // Check the cache directly
        $is_memcache_ok = $mc->get( $wp_object_cache->key( $key, $group ) );

        // If 'yes' got stored, Memcached must be working correctly
        return 'yes' !== $is_memcache_ok;

    }
}

WC_Cache_Helper::init();
