<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils;
use WP_Block;

/**
 * Block type for the CTA of grouped product selector items in add to cart with options.
 * It's responsible to render the CTA for each child product, that might be a button,
 * a checkbox, or a link.
 */
class GroupedProductSelectorItemCTA extends AbstractAddToCartWithOptionsBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-grouped-product-selector-item-cta';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		global $product;
		$previous_product = $product;

		$product = Utils::get_product_from_context( $block, $previous_product );
		$markup  = '';

		if ( $product ) {
			if ( ! $product->is_purchasable() || $product->has_options() || ! $product->is_in_stock() ) {
				$markup = $this->get_button_markup( $product );
			} elseif ( $product->is_sold_individually() ) {
				$markup = $this->get_checkbox_markup( $product );
			} else {
				$markup = $this->get_quantity_selector_markup( $product );
			}

			if ( $markup ) {
				$markup = '<div class="wp-block-add-to-cart-with-options-grouped-product-selector-item-cta wc-block-add-to-cart-with-options-grouped-product-selector-item-cta">' . $markup . '</div>';
			}
		}

		$product = $previous_product;

		return $markup;
	}
}
