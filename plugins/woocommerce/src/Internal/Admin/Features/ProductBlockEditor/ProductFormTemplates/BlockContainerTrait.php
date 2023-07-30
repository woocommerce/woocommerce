<?php

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockInterface;

/**
 * Trait for block containers.
 */
trait BlockContainerTrait {
	/**
	 * The child blocks.
	 *
	 * @var BlockInterface[]
	 */
	private $child_blocks = [];

	/**
	 * Add a block to the block container.
	 *
	 * @param array $block_config The block data.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 */
	public function &add_block( array $block_config ): BlockInterface {
		$root_template = $this->get_root_template();

		$block = new Block( $block_config, $root_template, $this );
		$root_template->internal_add_block_to_template( $block );
		$this->child_blocks[] = &$block;
		return $block;
	}

	/**
	 * Get the child blocks sorted by order.
	 */
	private function get_child_blocks_sorted_by_order(): array {
		$sorted_child_blocks = $this->child_blocks;

		usort(
			$sorted_child_blocks,
			function( Block $a, Block $b ) {
				return $a->get_order() <=> $b->get_order();
			}
		);

		return $sorted_child_blocks;
	}

	/**
	 * Get the child blocks as a simple array.
	 */
	public function get_child_blocks_as_simple_array(): array {
		$child_blocks = $this->get_child_blocks_sorted_by_order();

		$child_blocks_as_simple_arrays = array_map(
			function( Block $block ) {
				return $block->get_as_simple_array();
			},
			$child_blocks
		);

		return $child_blocks_as_simple_arrays;
	}
}
