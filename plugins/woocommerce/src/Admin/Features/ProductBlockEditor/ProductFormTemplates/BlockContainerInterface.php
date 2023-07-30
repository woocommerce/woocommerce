<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

/**
 * Interface for block containers.
 */
interface BlockContainerInterface {

	/**
	 * Add a block to the block container.
	 *
	 * @param array $block_config The block data.
	 */
	public function &add_block( array $block_config ): BlockInterface;

	/**
	 * Get the root template that the block container belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface;

	/**
	 * Get the child blocks as a simple array.
	 */
	public function get_child_blocks_as_simple_array(): array;
}
