<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * MiniCartInteractivity class.
 */
class MiniCartInteractivity extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-interactivity';

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
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

		/**
		 * Render the block.
		 *
		 * @param array    $attributes Block attributes.
		 * @param string   $content    Block content.
		 * @param WP_Block $block      Block instance.
		 * @return string Rendered block type output.
		 */
	protected function render( $attributes, $content, $block ) {
		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}
	}
}
