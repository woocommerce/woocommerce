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
	public static function get_product_data( $product ) {
		return array_merge(
			self::get_product_price_html( $product ),
			self::get_product_description( $product ),
		);
	}

	/**
	 * Get the product price HTML.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string The product price HTML.
	 */
	public static function get_product_price_html( $product ) {
		return array(
			'price_html' => $product->get_price_html(),
		);
	}

	/**
	 * Get the product description.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string The product description.
	 */
	public static function get_product_description( $product ) {
		return array(
			'description' => $product->get_description(),
		);
	}
}
