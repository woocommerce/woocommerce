<?php
/**
 * WC_Cache_Helper class.
 *
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Cache_Helper.
 */
class WC_Cache_Helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'geolocation_ajax_redirect' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'delete_version_transients', array( __CLASS__, 'delete_version_transients' ) );
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ) );
		add_action( 'clean_term_cache', array( __CLASS__, 'clean_term_cache' ), 10, 2 );
		add_action( 'edit_terms', array( __CLASS__, 'clean_term_cache' ), 10, 2 );
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed.
		$prefix = wp_cache_get( 'wc_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'wc_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'wc_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @param string $group Group of cache to clear.
	 */
	public static function incr_cache_prefix( $group ) {
		wp_cache_incr( 'wc_' . $group . '_cache_prefix', 1, $group );
	}

	/**
	 * Get a hash of the customer location.
	 *
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
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}
		$page_ids = array_filter( array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ), wc_get_page_id( 'myaccount' ) ) );

		if ( is_page( $page_ids ) ) {
			self::set_nocache_constants();
			nocache_headers();
		}
	}

	/**
	 * When using geolocation via ajax, to bust cache, redirect if the location hash does not equal the querystring.
	 *
	 * This prevents caching of the wrong data for this request.
	 */
	public static function geolocation_ajax_redirect() {
		if ( 'geolocation_ajax' === get_option( 'woocommerce_default_customer_address' ) && ! is_checkout() && ! is_cart() && ! is_account_page() && ! is_ajax() && empty( $_POST ) ) { // WPCS: CSRF ok, input var ok.
			$location_hash = self::geolocation_ajax_get_location_hash();
			$current_hash  = isset( $_GET['v'] ) ? wc_clean( wp_unslash( $_GET['v'] ) ) : ''; // WPCS: sanitization ok, input var ok, CSRF ok.
			if ( empty( $current_hash ) || $current_hash !== $location_hash ) {
				global $wp;

				$redirect_url = trailingslashit( home_url( $wp->request ) );

				if ( ! empty( $_SERVER['QUERY_STRING'] ) ) { // WPCS: Input var ok.
					$redirect_url = add_query_arg( wp_unslash( $_SERVER['QUERY_STRING'] ), '', $redirect_url ); // WPCS: sanitization ok, Input var ok.
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
	 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
	 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
	 *
	 * @param  string  $group   Name for the group of transients we need to invalidate.
	 * @param  boolean $refresh true to force a new version.
	 * @return string transient version based on time(), 10 digits.
	 */
	public static function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );
		$transient_value = strval( $transient_value ? $transient_value : '' );

		if ( '' === $transient_value || true === $refresh ) {
			$old_transient_value = $transient_value;
			$transient_value     = (string) time();

			if ( $old_transient_value === $transient_value ) {
				// Time did not change but transient needs flushing now.
				self::delete_version_transients( $transient_value );
			} else {
				self::queue_delete_version_transients( $transient_value );
			}

			set_transient( $transient_name, $transient_value );
		}
		return $transient_value;
	}

	/**
	 * Queues a cleanup event for version transients.
	 *
	 * @param string $version Version of the transient to remove.
	 */
	protected static function queue_delete_version_transients( $version = '' ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			wp_schedule_single_event( time() + 30, 'delete_version_transients', array( $version ) );
		}
	}

	/**
	 * When the transient version increases, this is used to remove all past transients to avoid filling the DB.
	 *
	 * Note; this only works on transients appended with the transient version, and when object caching is not being used.
	 *
	 * @since  2.3.10
	 * @param string $version Version of the transient to remove.
	 */
	public static function delete_version_transients( $version = '' ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			global $wpdb;

			$limit = apply_filters( 'woocommerce_delete_version_transients_limit', 1000 );

			if ( ! $limit ) {
				return;
			}

			$affected = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '\_transient\_%' . $version, $limit ) ); // WPCS: cache ok, db call ok.

			// If affected rows is equal to limit, there are more rows to delete. Delete in 30 secs.
			if ( $affected === $limit ) {
				self::queue_delete_version_transients( $version );
			}
		}
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		wc_maybe_define_constant( 'DONOTCACHEPAGE', true );
		wc_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		wc_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

	/**
	 * Notices function.
	 */
	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance( 'W3_Config' );
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_wc_session_', $settings, true ) ) {
			?>
			<div class="error">
				<p>
				<?php
				/* translators: 1: key 2: URL */
				echo wp_kses_post( sprintf( __( 'In order for <strong>database caching</strong> to work with WooCommerce you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', 'woocommerce' ), '<code>_wc_session_</code>', esc_url( admin_url( 'admin.php?page=w3tc_dbcache' ) ) ) );
				?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Clean term caches added by WooCommerce.
	 *
	 * @since 3.3.4
	 * @param array|int $ids Array of ids or single ID to clear cache for.
	 * @param string    $taxonomy Taxonomy name.
	 */
	public static function clean_term_cache( $ids, $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			$ids = is_array( $ids ) ? $ids : array( $ids );

			$clear_ids = array( 0 );

			foreach ( $ids as $id ) {
				$clear_ids[] = $id;
				$clear_ids   = array_merge( $clear_ids, get_ancestors( $id, 'product_cat', 'taxonomy' ) );
			}

			$clear_ids = array_unique( $clear_ids );

			foreach ( $clear_ids as $id ) {
				wp_cache_delete( 'product-category-hierarchy-' . $id, 'product_cat' );
			}
		}
	}
}

WC_Cache_Helper::init();
