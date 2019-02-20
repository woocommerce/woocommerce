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
	 * Init tracking.
	 */
	public static function init() {
		if ( ! self::is_tracking_enabled() ) {
			return;
		}

		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-products-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';

		$tracking_classes = array(
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
