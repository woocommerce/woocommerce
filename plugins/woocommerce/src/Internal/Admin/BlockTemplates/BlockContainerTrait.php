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

	// phpcs doesn't take into account exceptions thrown by called methods.
	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Add a block to the block container.
	 *
	 * @param BlockInterface $block The block.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 * @throws \UnexpectedValueException If the block container is not the parent of the block.
	 */
	protected function &add_inner_block( BlockInterface $block ): BlockInterface {
		if ( ! $block instanceof BlockInterface ) {
			throw new \UnexpectedValueException( 'The block must return an instance of BlockInterface.' );
		}

		if ( $block->get_parent() !== $this ) {
			throw new \UnexpectedValueException( 'The block container is not the parent of the block.' );
		}

		$root_template = $block->get_root_template();
		$root_template->cache_block( $block );
		$this->inner_blocks[] = &$block;
		return $block;
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

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
	public function get_formatted_template(): array {
		$arr = [
			$this->get_name(),
			$this->get_attributes(),
		];

		$inner_blocks = $this->get_inner_blocks_sorted_by_order();

		if ( ! empty( $inner_blocks ) ) {
			$arr[] = array_map(
				function( BlockInterface $block ) {
					return $block->get_formatted_template();
				},
				$inner_blocks
			);
		}

		return $arr;
	}
}
