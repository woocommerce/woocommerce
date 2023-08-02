<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

/**
 * Trait for block containers.
 */
trait BlockContainerTrait {
	/**
	 * The inner blocks.
	 *
	 * @var BlockInterface[]
	 */
	private $inner_blocks = [];

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
		$this->inner_blocks[] = &$block;
		return $block;
	}

	/**
	 * Get the inner blocks sorted by order.
	 */
	private function get_inner_blocks_sorted_by_order(): array {
		$sorted_inner_blocks = $this->inner_blocks;

		usort(
			$sorted_inner_blocks,
			function( Block $a, Block $b ) {
				return $a->get_order() <=> $b->get_order();
			}
		);

		return $sorted_inner_blocks;
	}

	/**
	 * Get the inner blocks as a formatted template.
	 */
	public function get_inner_blocks_formatted_template(): array {
		$inner_blocks = $this->get_inner_blocks_sorted_by_order();

		$inner_blocks_formatted_template = array_map(
			function( Block $block ) {
				return $block->get_formatted_template();
			},
			$inner_blocks
		);

		return $inner_blocks_formatted_template;
	}
}
