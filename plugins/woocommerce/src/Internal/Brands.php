<?php
/**
 * Brands class file.
 */

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Class to initiate Brands functionality in core.
 */
class Brands {

	/**
	 * Class initialization
	 *
	 * @internal
	 */
	final public static function init() {

		// If the WooCommerce Brands plugin is activated via the WP CLI using the '--skip-plugins' flag, deactivate it here.
		if ( function_exists( 'wc_brands_init' ) ) {
			remove_action( 'plugins_loaded', 'wc_brands_init', 1 );
		}
	}
}
