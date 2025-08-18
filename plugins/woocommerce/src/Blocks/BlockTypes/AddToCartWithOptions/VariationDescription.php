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
		$variations                = $product->get_available_variations( 'objects' );
		$formatted_variations_data = array();
		foreach ( $variations as $variation ) {
			$formatted_variations_data[ $variation->get_id() ] = array(
				'variationDescription' => $variation->get_description(),
			);
		}

		wp_interactivity_state(
			'woocommerce',
			array(
				'products' => array(
					$product->get_id() => array(
						'variations' => $formatted_variations_data,
					),
				),
			)
		);

		wp_enqueue_script_module( 'woocommerce/product-elements' );

		$wrapper_attributes = array(
			'data-wp-interactive'  => 'woocommerce/product-elements',
			'data-wp-text'         => 'state.productData.variationDescription',
			'data-wp-bind--hidden' => '!state.productData.variationDescription',
		);

		return '<div ' . get_block_wrapper_attributes( $wrapper_attributes ) . '></div>';
	}
}
