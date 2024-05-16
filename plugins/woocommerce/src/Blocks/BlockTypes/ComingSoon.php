<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ComingSoon class.
 */
class ComingSoon extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'coming-soon';

	/**
	 * It is necessary to register and enqueue assets during the render phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
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
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
