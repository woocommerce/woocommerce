<?php
/**
 * WooCommerce Analytics Tracking
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WC_Tracks_Client;
use WC_Tracks_Event;

/**
 * WooCommerce Analytics ClickHouse Client class
 */
class WC_Analytics_Ch_Event extends WC_Tracks_Event {
	/**
	 * The ClickHouse pixel URL.
	 *
	 * @var string
	 */
	const PIXEL = 'https://pixel.wp.com/w.gif';

	/**
	 * Build a pixel URL that will send a Tracks event when fired.
	 * On error, returns an empty string ('').
	 *
	 * @return string A pixel URL or empty string ('') if there were invalid args.
	 */
	public function build_pixel_url() {
		$pixel_url = parent::build_pixel_url();

		if ( empty( $pixel_url ) ) {
			return $pixel_url;
		}

		// Replace Tracks pixel URL with ClickHouse pixel URL.
		$pixel_url = str_replace( WC_Tracks_Client::PIXEL, self::PIXEL, $pixel_url );

		return $pixel_url;
	}
}
