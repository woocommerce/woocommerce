<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\Utils;

use WC_Product;

/**
 * Utility class for variation data loading configuration.
 *
 * @internal
 */
class VariationDataUtils {
	/**
	 * Check if variation data should be lazy loaded for this product.
	 *
	 * Uses the woocommerce_ajax_variation_threshold filter (default 30) to determine
	 * when to lazy load variation data, matching the behavior of classic templates.
	 *
	 * @param WC_Product|false|null $product The product to check variation count for.
	 * @return bool Whether variations should be lazy loaded.
	 */
	public static function should_lazy_load_variations( $product = null ): bool {
		// Products with more variations than the threshold use lazy loading.
		// This matches the behavior of classic templates.
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return false;
		}

		$threshold = apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		return count( $product->get_children() ) > $threshold;
	}
}
