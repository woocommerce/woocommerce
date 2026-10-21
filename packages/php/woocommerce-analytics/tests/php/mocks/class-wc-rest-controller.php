<?php
/**
 * Minimal WC_REST_Controller stub.
 *
 * WooCommerce is not loaded under WorDBless, so the real controller base class
 * is unavailable and anything extending it cannot be instantiated in tests. The
 * package only relies on the WP_REST_Controller behaviour it inherits, so an
 * empty subclass is enough.
 *
 * @package automattic/woocommerce-analytics
 */

if ( ! class_exists( 'WC_REST_Controller' ) ) {
	/**
	 * Stub for WooCommerce's REST controller base class.
	 */
	class WC_REST_Controller extends WP_REST_Controller {} // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
}
