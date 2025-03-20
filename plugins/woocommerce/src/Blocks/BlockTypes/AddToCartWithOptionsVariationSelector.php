<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Block type for variation selector in add to cart with options.
 */
class AddToCartWithOptionsVariationSelector extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector';

	/**
	 * Get variations data.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array|false
	 */
	private function get_variations_data( $product ) {
		/**
		 * Filter the number of variations threshold.
		 *
		 * @since 9.7.0
		 *
		 * @param int        $threshold Maximum number of variations to load upfront.
		 * @param WC_Product $product   Product object.
		 */
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		return $get_variations ? $product->get_available_variations() : false;
	}

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

		if ( $product instanceof \WC_Product && $product->is_type( 'variable' ) ) {
			$variation_attributes = $product->get_variation_attributes();

			if ( empty( $variation_attributes ) ) {
				return '';
			}

			$variations = $this->get_variations_data( $product );
			if ( empty( $variations ) ) {
				return '';
			}

			return $content;
		}

		return '';
	}

	/**
	 * Disable the frontend script for this block type, it's built with script modules.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
