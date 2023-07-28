<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates;

trait BlockContainerTrait {
	private $child_blocks = [];

	public function &add_block( array $block_data ): Block {
		$root_template = $this->get_root_template();

		$block = new Block( $block_data, $root_template, $this );
		$root_template->_add_block_to_template( $block );
		$this->child_blocks[] = &$block;
		return $block;
	}

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
