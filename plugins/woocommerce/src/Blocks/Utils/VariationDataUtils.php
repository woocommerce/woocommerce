<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\Utils;

use WC_Product;
use WP_Block;

/**
 * Utility class for variation data loading configuration.
 *
 * @internal
 */
class VariationDataUtils {
	/**
	 * Check if lazy loading of variation data is enabled.
	 *
	 * Checks in order:
	 * 1. Block context (from parent block attribute)
	 * 2. Variation count threshold via woocommerce_ajax_variation_threshold filter (default 30)
	 *
	 * @param WP_Block|null        $block   The block instance to check context from.
	 * @param WC_Product|false|null $product The product to check variation count for.
	 * @return bool Whether lazy loading is enabled.
	 */
	public static function is_enabled( ?WP_Block $block = null, $product = null ): bool {
		// Check block context first (set by parent block).
		if ( $block && isset( $block->context['woocommerce/lazyLoadVariations'] ) ) {
			return (bool) $block->context['woocommerce/lazyLoadVariations'];
		}

		// Fall back to threshold check (same logic as classic templates).
		// Products with more variations than the threshold use lazy loading.
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return false;
		}

		$threshold = apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		return count( $product->get_children() ) > $threshold;
	}
}
