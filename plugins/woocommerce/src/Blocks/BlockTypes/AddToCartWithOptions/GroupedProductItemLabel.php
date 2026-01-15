<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use WP_Block;

/**
 * Block type for the label of grouped product selector items in Add to Cart + Options.
 * It's responsible to render the label for each child product.
 */
class GroupedProductItemLabel extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-grouped-product-item-label';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		$product = AddToCartWithOptionsUtils::get_product_from_context( $block, $GLOBALS['product'] );
		$markup  = '';

		if ( $product ) {
			$wrapper_attributes = get_block_wrapper_attributes();
			$title              = $product->get_name();

			if ( ! $product->is_purchasable() || $product->has_options() || ! $product->is_in_stock() ) {
				$markup = sprintf(
					'<div %1$s>%2$s</div>',
					$wrapper_attributes,
					esc_html( $title )
				);
			} else {
				// Checkbox.
				$markup = sprintf(
					'<label %1$s for="%2$s">%3$s</label>',
					$wrapper_attributes,
					esc_attr( 'quantity_' . $product->get_id() ),
					esc_html( $title )
				);
			}
		}

		return $markup;
	}
}
