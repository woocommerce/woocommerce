<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Enums\ProductType;

/**
 * Utility methods used for the Add to Cart with Options block.
 * {@internal This class and its methods are not intended for public use.}
 */
class Utils {
	/**
	 * Get the normalized version of the attributes.
	 *
	 * @param array $attributes         The element's attributes.
	 * @param array $default_attributes The element's default attributes.
	 * @return string The HTML element's attributes.
	 */
	public static function get_normalized_attributes( $attributes, $default_attributes = array() ) {
		$normalized_attributes = array();

		$merged_attributes = array_merge( $default_attributes, $attributes );

		foreach ( $merged_attributes as $key => $value ) {
			if ( is_null( $value ) ) {
				continue;
			}
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = wp_json_encode(
					$value,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				);
			}
			$normalized_attributes[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return implode( ' ', $normalized_attributes );
	}

	/**
	 * Get product from block context.
	 *
	 * @param \WP_Block        $block The block instance.
	 * @param \WC_Product|null $previous_product The previous product (usually from global scope).
	 * @return \WC_Product|null The product instance or null if not found.
	 */
	public static function get_product_from_context( $block, $previous_product ) {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = null;

		if ( ! empty( $post_id ) ) {
			$product = wc_get_product( $post_id );
		}

		if ( ! $product instanceof \WC_Product && $previous_product instanceof \WC_Product ) {
			$product = $previous_product;
		}

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Check if a product is a simple product that is not purchasable or not in stock.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if the product is a simple product that is not purchasable or not in stock.
	 */
	public static function is_not_purchasable_simple_product( $product ) {
		return ProductType::SIMPLE === $product->get_type() && ( ! $product->is_in_stock() || ! $product->is_purchasable() );
	}

	/**
	 * Modifies the block context for product button blocks when inside the Add to Cart with Options block.
	 *
	 * @param array $context The block context.
	 * @param array $block   The parsed block.
	 * @return array Modified block context.
	 */
	public static function set_is_descendant_of_add_to_cart_with_options_context( $context, $block ) {
		if ( 'woocommerce/product-button' === $block['blockName'] ) {
			$context['woocommerce/isDescendantOfAddToCartWithOptions'] = true;
		}

		return $context;
	}
}
