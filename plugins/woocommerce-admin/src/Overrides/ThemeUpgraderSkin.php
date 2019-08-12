<?php
/**
 * Theme upgrader skin used in REST API response.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Admin\Overrides\ThemeUpgraderSkin Class.
 */
class ThemeUpgraderSkin extends \Theme_Upgrader_Skin {
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
