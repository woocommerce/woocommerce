<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use WP_Block;

/**
 * Block type for variation selector item in add to cart with options.
 * It's responsible to render each child attribute in a form of a list item.
 */
class VariationSelectorItemTemplate extends AbstractAddToCartWithOptionsBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-item';

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
		$attribute_name  = $product_attribute_name;
		$attribute_terms = $this->get_terms( $product_attribute_name, $product_attribute_terms );

		if ( empty( $attribute_terms ) ) {
			return '';
		}

		$context = $this->prepare_block_context(
			array(
				'name'  => $attribute_name,
				'terms' => $attribute_terms,
			)
		);

		// Render the inner blocks of the Variation Selector Item Template block with `dynamic` set to `false`
		// to prevent calling `render_callback` and ensure that no wrapper markup is included.
		return $this->render_block_with_context( $block, $context );
	}

	/**
	 * Prepare context for the block
	 * Implementation of the abstract method from the trait
	 *
	 * @param array $item Item data including name and terms.
	 * @return array Context for the block
	 */
	private function prepare_block_context( $item ) {
		return array(
			'woocommerce/attributeId'    => 'wc_product_attribute_' . uniqid(),
			'woocommerce/attributeName'  => $item['name'],
			'woocommerce/attributeTerms' => $item['terms'],
		);
	}
}
