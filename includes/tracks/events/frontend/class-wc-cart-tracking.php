<?php
/**
 * WooCommerce Status Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to trigger tracks events related to the cart.
 */
class WC_Cart_Tracking {

	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'woocommerce_after_cart', array( $this, 'track_cart_events' ) );
	}

	/**
	 * Add Tracks events to the cart page.
	 */
	public function track_cart_events() {
		wc_enqueue_js(
			"
				window.wcTracks.recordEvent( 'cart_viewed' );
			"
		);
	}
}
