<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;

/**
 * VariationDescription class.
 */
class VariationDescription extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-description';


	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		global $product;

		if ( ! $product instanceof \WC_Product_Variable ) {
			return '';
		}

		$context_directive = wp_interactivity_data_wp_context(
			array(
				'productElementKey' => 'description',
			)
		);

		$wrapper_attributes = array(
			'data-wp-interactive'  => 'woocommerce/product-elements',
			'data-wp-bind--hidden' => 'woocommerce/products::!state.productVariationInContext.description',
			'aria-live'            => 'polite',
			'aria-atomic'          => 'true',
		);

		return '<div ' . $context_directive . ' ' . get_block_wrapper_attributes( $wrapper_attributes ) . ' data-wp-watch="callbacks.updateValue"></div>';
	}
}
