<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Templates\ProductStockIndicator;
use Automattic\WooCommerce\Enums\ProductType;
/**
 * Utility functions for product availability.
 */
class ProductAvailabilityUtils {

	/**
	 * Get product availability information.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string[] The product availability class and text.
	 */
	public static function get_product_availability( $product ) {
		$product_availability = array(
			'availability' => '',
			'class'        => '',
		);

		if ( ! $product ) {
			return $product_availability;
		}

		// If the product is a variable product, check if it has any available variations.
		// We will show a custom availability message if it does.
		if ( $product->get_type() === ProductType::VARIABLE ) {
			$available_variations = $product->get_available_variations();
			if ( empty( $available_variations ) && false !== $available_variations ) {
				$product_availability['availability'] = __( 'This product is currently out of stock and unavailable.', 'woocommerce' );
				$product_availability['class']        = 'out-of-stock';
			}
		} else {
			$product_availability = $product->get_availability();
		}

		/**
		 * Filters the product availability information.
		 *
		 * @since 9.7.0
		 * @param array $product_availability The product availability information.
		 */
		return apply_filters( 'woocommerce_product_availability', $product_availability );
	}
}
