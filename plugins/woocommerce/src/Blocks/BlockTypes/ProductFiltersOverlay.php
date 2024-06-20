<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductFiltersOverlay class.
 */
class ProductFiltersOverlay extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters-overlay';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		ob_start();
		printf( '<div>%s</div>', esc_html__( 'Filters Overlay', 'woocommerce' ) );
		$html = ob_get_clean();

		return $html;
	}
}
