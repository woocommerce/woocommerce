<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductVariations;

use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;
use Automattic\WooCommerce\Utilities\CallbackUtil;

/**
 * The specialized optimization service, with the primary duty to hold context checks and identify optimization applicability.
 *
 * @internal do not use outside of Woo core - subject of change and removal without further notice.
 */
final class VariationsLoopOptimizationService {
	/**
	 * Verifies whether the get_available_variations/has_purchasable_variations methods can use meta lookup optimization.
	 * Our target is default variation class, without hooks affecting result returned by '$product->is_in_stock()'.
	 *
	 * @param mixed $probe Variation object for applicability verification.
	 * @return bool
	 */
	public function applicable_to_stock_status_meta( $probe ): bool {
		if ( $probe instanceof \WC_Product_Variation && \WC_Product_Variation::class === get_class( $probe ) ) {
			return ! has_filter( 'woocommerce_product_is_in_stock' ) && ! has_filter( 'woocommerce_product_variation_get_stock_status' );
		}

		return false;
	}

	/**
	 * Verifies whether build_variation_gallery_entry method can use direct meta read optimization.
	 * Our target is default variation class, without hooks affecting result returned by '$product->get_image_id()',  '$product->get_gallery_image_ids()'.
	 *
	 * @param mixed $probe Variation object for applicability verification.
	 * @return bool
	 */
	public function applicable_to_image_metas( $probe ): bool {
		if ( $probe instanceof \WC_Product_Variation && \WC_Product_Variation::class === get_class( $probe ) ) {
			$actual_gallery_callbacks   = CallbackUtil::get_hook_callback_signatures( 'woocommerce_product_variation_get_gallery_image_ids' );
			$expected_gallery_callbacks = array( 10 => array( LegacyVariationGalleryCompatibility::class . '::maybe_read_legacy_gallery_image_ids' ) );

			return $actual_gallery_callbacks === $expected_gallery_callbacks && ! has_filter( 'woocommerce_product_variation_get_image_id' );
		}

		return false;
	}
}
