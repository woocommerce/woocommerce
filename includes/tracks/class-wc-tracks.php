<?php
/**
 * PHP Tracks Client
 *
 * @package WooCommerce\Tracks
 */

/**
 * WC_Tracks class.
 */
class WC_Tracks {

	/**
	 * Tracks event name prefix.
	 */
	const PREFIX = 'wcadmin_';

	/**
	 * Option name for total store revenue.
	 */
	const REVENUE_CACHE_OPTION = 'woocommerce_tracker_orders_totals';

	/**
	 * Initialize necessary hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_new_order', array( __CLASS__, 'update_revenue_cache' ) );
		add_action( 'woocommerce_update_order', array( __CLASS__, 'update_revenue_cache' ) );
		add_action( 'woocommerce_delete_order', array( __CLASS__, 'update_revenue_cache' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'update_revenue_cache' ) );
	}

	/**
	 * Recalculate store gross revenue and update cache.
	 */
	public static function update_revenue_cache() {
		update_option( self::REVENUE_CACHE_OPTION, WC_Tracker::get_order_totals() );
	}

	/**
	 * Get the (cached) store gross revenue total.
	 *
	 * @return null|string Store gross revenue, or null if cache error.
	 */
	public static function get_total_revenue() {
		$total_revenue = get_option( self::REVENUE_CACHE_OPTION, false );

		if ( false === $total_revenue ) {
			$total_revenue = WC_Tracker::get_order_totals();

			update_option( self::REVENUE_CACHE_OPTION, $total_revenue );
		}

		return empty( $total_revenue['gross'] ) ? null : $total_revenue['gross'];
	}

	/**
	 * Get total product counts.
	 *
	 * @return int Number of products.
	 */
	public static function get_products_count() {
		$product_counts = WC_Tracker::get_product_counts();
		return $product_counts['total'];
	}

	/**
	 * Gather blog related properties.
	 *
	 * @param int $user_id User id.
	 * @return array Blog details.
	 */
	public static function get_blog_details( $user_id ) {
		$blog_details = get_transient( 'wc_tracks_blog_details' );
		if ( false === $blog_details ) {
			$blog_details = array(
				'url'            => home_url(),
				'blog_lang'      => get_user_locale( $user_id ),
				'blog_id'        => ( class_exists( 'Jetpack' ) && Jetpack_Options::get_option( 'id' ) ) || null,
				'products_count' => self::get_products_count(),
				'orders_gross'   => self::get_total_revenue(),
			);
			set_transient( 'wc_tracks_blog_details', $blog_details, DAY_IN_SECONDS );
		}
		return $blog_details;
	}

	/**
	 * Gather details from the request to the server.
	 *
	 * @return array Server details.
	 */
	public static function get_server_details() {
		$data = array();

		$data['_via_ua'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$data['_via_ip'] = isset( $_SERVER['REMOTE_ADDR'] ) ? wc_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$data['_lg']     = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$data['_dr']     = isset( $_SERVER['HTTP_REFERER'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$uri         = isset( $_SERVER['REQUEST_URI'] ) ? wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$data['_dl'] = isset( $_SERVER['REQUEST_SCHEME'] ) ? wc_clean( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) . '://' . $host . $uri : '';

		return $data;
	}

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	public static function record_event( $event_name, $properties = array() ) {
		/**
		 * Don't track users who haven't opted-in to tracking or if a filter
		 * has been applied to turn it off.
		 */
		if (
			'yes' !== get_option( 'woocommerce_allow_tracking' ) ||
			! apply_filters( 'woocommerce_apply_tracking', true )
		) {
			return false;
		}

		$user = wp_get_current_user();

		// We don't want to track user events during unit tests/CI runs.
		if ( $user instanceof WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return false;
		}

		$data = array(
			'_en' => self::PREFIX . $event_name,
			'_ts' => WC_Tracks_Client::build_timestamp(),
		);

		$server_details = self::get_server_details();
		$identity       = WC_Tracks_Client::get_identity( $user->ID );
		$blog_details   = self::get_blog_details( $user->ID );

		$event_obj = new WC_Tracks_Event( array_merge( $data, $server_details, $identity, $blog_details, $properties ) );

		if ( is_wp_error( $event_obj->error ) ) {
			return $event_obj->error;
		}

		return $event_obj->record();
	}
}

WC_Tracks::init();
