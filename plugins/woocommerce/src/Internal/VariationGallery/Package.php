<?php
/**
 * Package class file for the variation gallery feature.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

defined( 'ABSPATH' ) || exit;

/**
 * Variation gallery package entry point.
 *
 * Registered in `\Automattic\WooCommerce\Packages::$merged_packages` against
 * the `woocommerce-additional-variation-images` slug, so this class is the
 * single bootstrap surface for the merged variation gallery feature.
 */
class Package {

	/**
	 * Whether the merged variation gallery feature is enabled for the current
	 * request.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return true;
	}

	public static function prepare() {
	}

	/**
	 * Initialize the merged variation gallery feature.
	 *
	 * @internal
	 */
	final public static function init() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$container = wc_get_container();
		$container->get( ClassicVariationGalleryAdmin::class )->register();
		$container->get( LegacyVariationGalleryCompatibility::class )->register();
	}
}
