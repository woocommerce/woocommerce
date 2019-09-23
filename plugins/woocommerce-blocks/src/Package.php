<?php
/**
 * Returns information about the package and handles init.
 *
 * @package Automattic/WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package as NewPackage;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 *
 * @deprecated $VID:$
 */
class Package {

	/**
	 * For back compat this is provided. Ideally, you should register your
	 * class with Automattic\Woocommerce\Blocks\Container and make Package a
	 * dependency.
	 *
	 * @since $VID:$
	 * @return Package  The Package instance class
	 */
	protected static function get_package() {
		return wc_blocks_container()->get( NewPackage::class );
	}

	/**
	 * Init the package - load the blocks library and define constants.
	 *
	 * @since $VID:$ Handled by new NewPackage.
	 */
	public static function init() {
		// noop.
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::get_package()->get_version();
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return self::get_package()->get_path();
	}
}
