<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * SingleProduct class.
 */
class SingleProduct extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'single-product';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string;
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-vendors', 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Render the block on the frontend.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		return $this->inject_html_data_attributes( $content, $attributes );
	}
}
