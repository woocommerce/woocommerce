<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

/**
 * Block type for variation selector in add to cart with options.
 */
class VariationSelector extends AbstractAddToCartWithOptionsBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
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

			add_filter( 'woocommerce_product_supports', array( $this, 'check_product_supports_wrapper' ), 10, 3 );

			return $content;
		}

		return '';
	}

	/**
	 * Checks if the product supports a specific feature.
	 *
	 * @param bool       $supports Whether the product supports the feature.
	 * @param string     $feature  The feature being checked.
	 * @param WC_Product $product  The product being checked.
	 * @return bool
	 */
	public function check_product_supports_wrapper( $supports, $feature, $product ) {
		return parent::check_product_supports( $supports, $feature, $product );
	}
}
