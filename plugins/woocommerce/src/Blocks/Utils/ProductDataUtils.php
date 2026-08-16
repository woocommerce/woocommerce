<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility class to get product data consumable by the blocks.
 *
 * @internal
 */
class ProductDataUtils {
	/**
	 * Get the product data.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array The product data.
	 */
	public static function get_product_data( \WC_Product $product ) {
		return array(
			'price_html' => $product->get_price_html(),
		);
	}
}
