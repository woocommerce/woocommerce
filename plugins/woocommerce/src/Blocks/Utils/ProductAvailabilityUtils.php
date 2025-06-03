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

		$product_availability = $product->get_availability();
		// If the product is a variable product and availability isn't controlled
		// at the parent product level, check if any of its variations is in stock.
		// We will show a custom availability message if all variations are out of stock.
		if ( ! $product_availability && $product->get_type() === ProductType::VARIABLE ) {
			if ( ! $product->has_available_variations() ) {
				$product_availability['availability'] = __( 'This product is currently out of stock and unavailable.', 'woocommerce' );
				$product_availability['class']        = 'out-of-stock';
			}
		}

		return $product_availability;
	}
}
