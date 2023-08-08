<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

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
	 * @param array    $block_config The block data.
	 * @param callable $block_creator An optional function that returns a new BlockInterface instance.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 * @throws \UnexpectedValueException If the block container is not the parent of the block.
	 */
	protected function &add_inner_block( array $block_config, $block_class = 'Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block' ): BlockInterface {
		$root_template = $this->get_root_template();

		$block_class = apply_filters( 'woocommerce_block_template_block_class', $block_class, $block_config, $root_template, $this );
		$block       = new $block_class( $block_config, $root_template, $this );

		if ( ! $block instanceof BlockInterface ) {
			throw new \UnexpectedValueException( 'The block must return an instance of BlockInterface.' );
		}

		if ( $block->get_parent() !== $this ) {
			throw new \UnexpectedValueException( 'The block container is not the parent of the block.' );
		}

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
