<?php
/**
 * Returns information about the package and handles init.
 *
 * @package Automattic/WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package as NewPackage;
use Automattic\WooCommerce\Blocks\Domain\Bootstrap;
use Automattic\WooCommerce\Blocks\Registry\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class. (Loader in the WC Core context as well)
 *
 * @since 2.5.0
 */
class Package {

	/**
	 * For back compat this is provided. Ideally, you should register your
	 * class with Automattic\Woocommerce\Blocks\Container and make Package a
	 * dependency.
	 *
	 * @since 2.5.0
	 * @return Package  The Package instance class
	 */
	protected static function get_package() {
		return self::container()->get( NewPackage::class );
	}

	/**
	 * Init the package - load the blocks library and define constants.
	 *
	 * @since 2.5.0 Handled by new NewPackage.
	 */
	public static function init() {
		self::container()->get( Bootstrap::class );
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

	/**
	 * Loads the dependency injection container for woocommerce blocks.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance.
	 *                       Note: this means all dependencies will be
	 *                       reconstructed.
	 */
	public static function container( $reset = false ) {
		static $container;
		if (
				! $container instanceof Container
				|| $reset
			) {
			$container = new Container();
			// register Package.
			$container->register(
				NewPackage::class,
				function ( $container ) {
					// leave for automated version bumping.
					$version = '2.5.0';
					return new NewPackage(
						$version,
						WC_BLOCKS_PLUGIN_FILE
					);
				}
			);
			// register Bootstrap.
			$container->register(
				Bootstrap::class,
				function ( $container ) {
					return new Bootstrap(
						$container
					);
				}
			);
		}
		return $container;
	}
}
