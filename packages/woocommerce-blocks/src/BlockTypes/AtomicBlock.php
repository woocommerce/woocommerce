<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * AtomicBlock class.
 *
 * @internal
 */
class AtomicBlock extends AbstractBlock {
	/**
	 * Inject attributes and block name.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		return $this->inject_html_data_attributes( $content, $attributes );
	}

	/**
	 * Get the editor script data for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return null
	 */
	protected function get_block_type_editor_script( $key = null ) {
		return null;
	}

	/**
	 * Get the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
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
	 * Converts block attributes to HTML data attributes.
	 *
	 * @param array $attributes Key value pairs of attributes.
	 * @return string Rendered HTML attributes.
	 */
	protected function get_html_data_attributes( array $attributes ) {
		$data = parent::get_html_data_attributes( $attributes );
		return trim( $data . ' data-block-name="' . esc_attr( $this->namespace . '/' . $this->block_name ) . '"' );
	}
}
