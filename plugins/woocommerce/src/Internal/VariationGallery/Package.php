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
	 * The feature id used by `FeaturesController` (Settings → Advanced → Features).
	 */
	public const FEATURE_ID = 'variation_gallery';

	public const ENABLE_OPTION_NAME = 'wc_feature_woocommerce_additional_variation_images_enabled';

	/**
	 * Whether the merged variation gallery feature is enabled for the current
	 * request.
	 *
	 * Returns false during the 10.9 canary period. After 100% rollout this
	 * method becomes `return true;` (matching Brands' shipped state).
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return false;
	}

	/**
	 * Early bootstrap hook fired by `Packages::prepare_packages` at
	 * plugins_loaded priority -100. No-op for the variation gallery feature.
	 *
	 * @internal
	 */
	public static function prepare(): void {
	}

	/**
	 * Initialize the merged variation gallery feature.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$container = wc_get_container();
		$container->get( ClassicVariationGalleryAdmin::class )->register();
		$container->get( LegacyVariationGalleryCompatibility::class )->register();
	}
}
