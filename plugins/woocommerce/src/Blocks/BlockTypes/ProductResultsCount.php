<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Buffer the result count and use it as the block's frontend content.
		ob_start();
		woocommerce_result_count();
		$product_results_count = ob_get_clean();

		$classes = 'woocommerce wc-block-product-results-count';
		if ( ! empty( $attributes['fontSize'] ) ) {
			// Preserve the legacy class for backward compatibility.
			$classes .= ' has-font-size';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'                 => $classes,
				'data-wp-interactive'   => $this->get_full_block_name(),
				'data-wp-router-region' => 'wc-product-results-count-' . ( $block->context['queryId'] ?? 0 ),
			)
		);

		return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $product_results_count );
	}
}
