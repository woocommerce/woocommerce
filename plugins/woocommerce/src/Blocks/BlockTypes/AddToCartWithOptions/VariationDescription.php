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

		$variations                = $product->get_available_variations( 'objects' );
		$formatted_variations_data = array();
		foreach ( $variations as $variation ) {
			$variation_description = $variation->get_description();
			if ( is_string( $variation_description ) && ! empty( $variation_description ) ) {
				$formatted_variations_data[ $variation->get_id() ] = array(
					'variation_description' => wp_kses_post( wc_format_content( $variation_description ) ),
				);
			}
		}

		wp_interactivity_config(
			'woocommerce',
			array(
				'products' => array(
					$product->get_id() => array(
						'variations' => $formatted_variations_data,
					),
				),
			)
		);

		$context_directive = wp_interactivity_data_wp_context(
			array(
				'productElementKey' => 'variation_description',
			)
		);

		$wrapper_attributes = array(
			'data-wp-interactive'  => 'woocommerce/product-elements',
			'data-wp-bind--hidden' => '!state.productData.variation_description',
			'aria-live'            => 'polite',
			'aria-atomic'          => 'true',
		);

		return '<div ' . $context_directive . ' ' . get_block_wrapper_attributes( $wrapper_attributes ) . ' data-wp-watch="callbacks.updateValue"></div>';
	}
}
