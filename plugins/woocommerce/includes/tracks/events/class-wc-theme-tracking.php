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
		$this->track_initial_theme();
		add_action( 'switch_theme', array( $this, 'track_activated_theme' ) );
	}

	/**
	 * Tracking the initial theme being used for the first time.
	 */
	public function track_initial_theme() {
		$has_been_initially_tracked = get_option( 'wc_has_tracked_default_theme' );

		if ( $has_been_initially_tracked ) {
			return;
		}

		$this->track_activated_theme();
		add_option( 'wc_has_tracked_default_theme', 1 );
	}

	/**
	 * Send a Tracks event when a theme is activated so that we can track active block themes.
	 */
	public function track_activated_theme() {
		$is_block_theme = false;
		$theme_object = wp_get_theme();

		if ( function_exists( 'wc_current_theme_is_fse_theme' ) ) {
			$is_block_theme = wc_current_theme_is_fse_theme();
		}

		$properties = array(
			'block_theme' => $is_block_theme,
			'theme_domain' => $theme_object->get( 'TextDomain' ),
		);

		WC_Tracks::record_event( 'activated_theme', $properties );
	}
}
