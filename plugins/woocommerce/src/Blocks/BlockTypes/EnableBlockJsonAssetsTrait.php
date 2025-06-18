<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

trait EnableBlockJsonAssetsTrait {

	/**
	 * Disable the script handle for this block type. We use block.json to load the script.
	 *
	 * @param string|null $key The key of the script to get.
	 * @return null
	 */
	// phpcs:ignore
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Disable the style handle for this block type. We use block.json to load the style.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		if ( wp_is_block_theme() ) {
			return null;
		}

		$this->asset_api->register_style( 'woocommerce-' . $this->block_name . '-style', 'assets/client/blocks/woocommerce/' . $this->block_name . '-style.css', [], 'all', true );

		return [ 'wc-blocks-style', 'woocommerce-' . $this->block_name . '-style' ];
	}

	/**
	 * Disable the editor style handle for this block type. We use block.json to load the style.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
