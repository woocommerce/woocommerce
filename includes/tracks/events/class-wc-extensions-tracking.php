<?php
/**
 * WooCommerce Extensions Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of the WooCommerce Extensions page.
 */
class WC_Extensions_Tracking {
	/**
	 * Init tracking.
	 */
	public static function init() {
		add_action( 'load-woocommerce_page_wc-addons', array( __CLASS__, 'track_extensions_page' ) );
	}

	/**
	 * Send a Tracks event when an Extensions page is viewed.
	 */
	public static function track_extensions_page() {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
		$properties = array(
			'section' => empty( $_REQUEST['section'] ) ? '_featured' : wc_clean( wp_unslash( $_REQUEST['section'] ) ),
		);
		// phpcs:enable

		WC_Tracks::record_event( 'extensions_view', $properties );
	}
}
