<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductFilters class.
 */
class ProductFilters extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters';

	/**
	 * Register the context.
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return array( 'postId' );
	}

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
		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-filters' ) ) ) {
			$tags->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocmmerce/' . $this->block_name ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
			$tags->set_attribute( 'data-wc-navigation-id', $this->generate_navigation_id( $block ) );
			return $tags->get_updated_html();
		}
	}

	/**
	 * Generate a unique navigation ID for the block.
	 *
	 * @param mixed $block - Block instance.
	 * @return string - Unique navigation ID.
	 */
	private function generate_navigation_id( $block ) {
		return sprintf(
			'wc-product-filters-%s',
			md5( wp_json_encode( $block->parsed_block['innerBlocks'] ) )
		);
	}
}
