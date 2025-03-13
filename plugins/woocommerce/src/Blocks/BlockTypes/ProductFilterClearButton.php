<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Clear Button Block.
 */
final class ProductFilterClearButton extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-clear-button';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 *
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
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
		// don't render if its admin, or ajax in progress.
		if (
			is_admin() ||
			wp_doing_ajax() ||
			empty( $block->context['filterData'] ) ||
			empty( $block->context['filterData']['parent'] )
		) {
			return '';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wp-bind--hidden' => '!state.hasSelectedFilters',
				'data-wp-interactive'  => $block->context['filterData']['parent'],
			)
		);

		$p = new \WP_HTML_Tag_Processor( $content );

		if ( $p->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) ) {
			$p->set_attribute( 'data-wp-on--click', 'actions.clearFilters' );

			$style = $p->get_attribute( 'style' );
			$p->set_attribute( 'style', 'outline:none;' . $style );

			$content = $p->get_updated_html();
		}

		$content = str_replace( array( '<a', '</a>' ), array( '<button', '</button>' ), $content );

		return sprintf(
			'<div %1$s hidden>%2$s</div>',
			$wrapper_attributes,
			$content
		);
	}
}
