<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods for the product-scoped add to cart context.
 */
class ProductAddToCartUtils {
	/**
	 * Product add to cart store namespace.
	 */
	public const STORE_NAMESPACE = 'woocommerce/add-to-cart';

	/**
	 * Get the default product-scoped add to cart context.
	 *
	 * @param \WC_Product|null $product Product for initial quantity.
	 * @return array Product-scoped add to cart context.
	 */
	public static function get_context( ?\WC_Product $product = null ): array {
		$quantity = array();

		if ( $product instanceof \WC_Product ) {
			$quantity[ $product->get_id() ] = $product->get_min_purchase_quantity();
		}

		return array(
			'quantity'           => $quantity,
			'selectedAttributes' => array(),
			'validationErrors'   => array(),
		);
	}

	/**
	 * Initialize the global fallback product add to cart state.
	 *
	 * @param \WC_Product|null $product Product for initial quantity.
	 */
	public static function initialize_state( ?\WC_Product $product = null ): void {
		wp_interactivity_state( self::STORE_NAMESPACE, self::get_context( $product ) );
	}

	/**
	 * Get the product add to cart context directive.
	 *
	 * @param \WC_Product|null $product Product for initial quantity.
	 * @return string Product add to cart context directive.
	 */
	public static function get_context_directive( ?\WC_Product $product = null ): string {
		return wp_interactivity_data_wp_context(
			self::get_context( $product ),
			self::STORE_NAMESPACE
		);
	}

	/**
	 * Wrap content in a product-scoped add to cart context without changing layout.
	 *
	 * @param string           $content Content to wrap.
	 * @param \WC_Product|null $product Product for initial quantity.
	 * @return string Wrapped content.
	 */
	public static function wrap_content( string $content, ?\WC_Product $product = null ): string {
		$wrapper_attributes = wc_implode_html_attributes(
			array(
				'class'               => 'wc-block-product-add-to-cart-context',
				'style'               => 'display: contents;',
				'data-wp-interactive' => self::STORE_NAMESPACE,
			)
		);

		return sprintf(
			'<div %1$s %2$s>%3$s</div>',
			$wrapper_attributes,
			self::get_context_directive( $product ),
			$content
		);
	}
}
