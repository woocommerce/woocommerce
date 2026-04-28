<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Shopper Collection block.
 *
 * Renders a collection curated by the shopper (e.g. Save for Later). The data
 * source and frontend interactivity are wired in follow-up tasks; this class
 * is the structural backbone that registers the block and outputs the
 * server-side container the iAPI store will hydrate.
 */
final class ShopperCollection extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'shopper-collection';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$list_name      = isset( $attributes['listName'] ) && is_string( $attributes['listName'] )
			? sanitize_key( $attributes['listName'] )
			: 'save-for-later';
		$show_quantity  = ! empty( $attributes['showQuantity'] );
		$show_variation = ! empty( $attributes['showVariation'] );
		$show_saved_at  = ! empty( $attributes['showSavedDate'] );

		$context = array(
			'listName'      => $list_name,
			'showQuantity'  => $show_quantity,
			'showVariation' => $show_variation,
			'showSavedDate' => $show_saved_at,
		);

		$context_json = (string) wp_json_encode(
			$context,
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		);

		$column_count = isset( $attributes['layout']['columnCount'] ) && is_numeric( $attributes['layout']['columnCount'] )
			? max( 1, (int) $attributes['layout']['columnCount'] )
			: 3;

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce',
			'data-wp-context'     => $context_json,
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
			'style'               => sprintf( '--wc-shopper-collection-columns:%d;', $column_count ),
		);

		return sprintf(
			'<ul %1$s>%2$s</ul>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			$this->get_placeholder_markup()
		);
	}

	/**
	 * Placeholder markup for the rendered block. The real `data-wp-each` template
	 * over `state.currentListItems` will be added once the iAPI store lands.
	 *
	 * @return string
	 */
	private function get_placeholder_markup(): string {
		return sprintf(
			'<li class="wc-block-shopper-collection__empty">%s</li>',
			esc_html__( 'Nothing saved yet — items you save from the cart will appear here.', 'woocommerce' )
		);
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * Scripts are loaded via `viewScriptModule` in block.json.
	 *
	 * @param string|null $key The key of the script to get.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
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
	 * Disable the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
