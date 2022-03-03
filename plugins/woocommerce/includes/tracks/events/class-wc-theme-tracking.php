<?php
/**
 * WooCommerce Theme Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of a WooCommerce Order.
 */
class WC_Theme_Tracking {

	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'switch_theme', array( $this, 'track_theme_activated' ) );
	}

	/**
	 * Send a Tracks event when a theme is activated so that we can track active block themes.
	 */
	public function track_theme_activated() {
		if ( ! function_exists( 'wc_current_theme_is_fse_theme' ) ) {
			return;
		}

		$properties = array(
			'block_theme' => wc_current_theme_is_fse_theme(),
		);

		WC_Tracks::record_event( 'theme_activated', $properties );
	}
}

