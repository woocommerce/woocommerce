<?php
/**
 * Nosara Tracks for WooCommerce
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce.
 */
class WC_Site_Tracking {
	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		/**
		 * Don't track users who haven't opted-in to tracking or if a filter
		 * has been applied to turn it off.
		 */
		if ( 'yes' !== get_option( 'woocommerce_allow_tracking' ) ||
			! apply_filters( 'woocommerce_apply_user_tracking', true ) ) {
			return false;
		}

		if ( ! class_exists( 'WC_Tracks' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add scripts required to record events from javascript.
	 */
	public static function enqueue_scripts() {

		// Add w.js to the page.
		wp_enqueue_script( 'woo-tracks', 'https://stats.wp.com/w.js', array(), gmdate( 'YW' ), true );

		// Expose tracking via a function in the wcTracks global namespace.
		wc_enqueue_js(
			"
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.recordEvent = function( name, properties ) {
				var eventName = '" . WC_Tracks::PREFIX . "' + name;
				var eventProperties = properties || {};
				eventProperties.url = '" . home_url() . "'
				eventProperties.products_count = '" . WC_Tracks::get_products_count() . "'
				eventProperties.orders_gross = '" . WC_Tracks::get_total_revenue() . "'
				window._tkq = window._tkq || [];
				window._tkq.push( [ 'recordEvent', eventName, eventProperties ] );
			}
		"
		);
	}

	/**
	 * Init tracking.
	 */
	public static function init() {

		if ( ! self::is_tracking_enabled() ) {

			// Define window.wcTracks.recordEvent in case there is an attempt to use it when tracking is turned off.
			wc_enqueue_js(
				'
				window.wcTracks = window.wcTracks || {};
				window.wcTracks.recordEvent = function() {};
			'
			);

			return;
		}

		self::enqueue_scripts();

		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-extensions-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-products-tracking.php';

		$tracking_classes = array(
			'WC_Extensions_Tracking',
			'WC_Importer_Tracking',
			'WC_Products_Tracking',
		);

		foreach ( $tracking_classes as $tracking_class ) {
			$init_method = array( $tracking_class, 'init' );

			if ( is_callable( $init_method ) ) {
				call_user_func( $init_method );
			}
		}
	}
}
