<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Shopper Collection: Cart Button block.
 *
 * Renders the "Move to cart" button for a saved-list row. The runtime click
 * action lives on the shared `woocommerce` iAPI namespace and reads the
 * per-row item from context. That wiring lands in the iAPI store follow-up;
 * for now this class only emits the structural markup.
 */
final class ShopperCollectionCartButton extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'shopper-collection-cart-button';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$text = isset( $attributes['text'] ) && is_string( $attributes['text'] ) && '' !== $attributes['text']
			? $attributes['text']
			: __( 'Move to cart', 'woocommerce' );

		$wrapper_attributes = array(
			'class' => 'wp-block-button wc-block-components-product-button wc-block-shopper-collection-cart-button',
		);

		return sprintf(
			'<div %1$s><button type="button" class="wp-block-button__link wp-element-button wc-block-components-product-button__button" data-wp-on--click="actions.moveToCartFromList">%2$s</button></div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			esc_html( $text )
		);
	}

	/**
	 * Scripts are loaded via `viewScriptModule` in block.json.
	 *
	 * @param string|null $key The key of the script to get.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Style handle: rely on block.json registration.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Editor style handle: rely on block.json registration.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
