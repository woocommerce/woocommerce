<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;
use WP_Block;

/**
 * Block type for variation selector item in add to cart with options.
 * It's responsible to render each child attribute in a form of a list item.
 */
class AddToCartWithOptionsVariationSelectorItemTemplate extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-item';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
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

		$content = '';

		$product_attributes = $product->get_variation_attributes();

		foreach ( $product_attributes as $product_attribute_name => $product_attribute_terms ) {
			$content .= $this->get_product_row( $product_attribute_name, $product_attribute_terms, $attributes, $block );
		}

		return $content;
	}

	/**
	 * Get product row HTML.
	 *
	 * @param string   $product_attribute_name Product Attribute Name.
	 * @param array    $product_attribute_terms Product Attribute Terms.
	 * @param array    $attributes Block attributes.
	 * @param WP_Block $block The Block.
	 * @return string Row HTML
	 */
	private function get_product_row( $product_attribute_name, $product_attribute_terms, $attributes, $block ): string {
		global $attribute_name;
		global $attribute_terms;

		$attribute_name = $product_attribute_name;
		$attribute_terms = $this->get_terms( $product_attribute_name, $product_attribute_terms );

		if ( empty( $attribute_terms ) ) {
			return '';
		}

		// Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
		// `render_callback` and ensure that no wrapper markup is included.
		return $block->render( array( 'dynamic' => false ) );
	}

	/**
	 * Get product attributes terms.
	 *
	 * @param string $attribute_name Product Attribute Name.
	 * @param array  $product_attribute_terms Product Attribute Terms.
	 * @return srtring
	 */
	protected function get_terms( $attribute_name, $attribute_terms ) {
		global $product;

		$is_taxonomy = taxonomy_exists( $attribute_name );
		if ( $is_taxonomy ) {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product ) {
					return array(
						'value' => $term->slug,
						'label' => apply_filters(
							'woocommerce_variation_option_name',
							$term->name,
							$term,
							$attribute_name,
							$product
						),
					);
				},
				wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) ),
			);
		} else {
			$items = array_map(
				function ( $term ) use ( $attribute_name, $product ) {
					return array(
						'value' => $term,
						'label' => apply_filters(
							'woocommerce_variation_option_name',
							$term,
							null,
							$attribute_name,
							$product
						),
					);
				},
				$attribute_terms,
			);
		}

		return $items;
	}
}
