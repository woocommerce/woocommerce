<?php
/**
 * Theme upgrader skin used in REST API response.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Theme_Upgrader_Skin Class.
 */
class WC_Admin_Theme_Upgrader_Skin extends Theme_Upgrader_Skin {
	/**
	 * Hide the skin header display.
	 */
	public function header() {}

	/**
	 * Hide the skin footer display.
	 */
	public function footer() {}

	/**
	 * Hide the skin feedback display.
	 *
	 * @param string $string String to display.
	 */
	public function feedback( $string ) {}

	/**
	 * Hide the skin after display.
	 */
	public function after() {}
}
