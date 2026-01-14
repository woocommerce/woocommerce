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

		/**
		 * Filter the threshold for loading variation data via AJAX vs inline.
		 *
		 * Products with more variations than this threshold will have their variation
		 * data loaded via AJAX when a variation is selected, rather than embedding
		 * all variation data in the page HTML. This reduces initial page load time
		 * for products with many variations.
		 *
		 * This filter is shared with classic templates for consistent behavior.
		 *
		 * @since 2.4.0 Introduced in classic templates.
		 * @since 10.5.0 Also used by blocks for consistent behavior.
		 *
		 * @param int        $threshold The variation count threshold. Default 30.
		 * @param WC_Product $product   The variable product being displayed.
		 */
		$threshold = apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		return count( $product->get_children() ) > $threshold;
	}
}
