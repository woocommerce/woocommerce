<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductResultsCount class.
 */
class ProductResultsCount extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-results-count';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

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
		// Buffer the result count and use it as the block's frontend content.
		ob_start();
		echo '<div>';
		woocommerce_result_count();
		echo '</div>';
		$product_results_count = ob_get_clean();

		$p = new \WP_HTML_Tag_Processor( $product_results_count );

		// Advance to the wrapper and add the attributes necessary for the block.
		$p->next_tag( 'div' );
		$parsed_style_attributes = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$classes                 = array_merge(
			explode( ' ', $parsed_style_attributes['classes'] ),
			array(
				'woocommerce',
				'wc-block-product-results-count',
				'wp-block-woocommerce-product-results-count',
			),
			isset( $attributes['className'] ) ? array( $attributes['className'] ) : array(),
		);
		$p->set_attribute( 'class', implode( ' ', $classes ) );
		$p->set_attribute( 'style', $parsed_style_attributes['styles'] );
		$p->set_attribute(
			'data-wc-navigation-id',
			'wc-product-results-count-' . ( isset( $block->context['queryId'] ) ? $block->context['queryId'] : 0 )
		);

		return $p->get_updated_html();
	}
}
