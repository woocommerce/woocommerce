<?php

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Interface for block containers.
 */
interface BlockContainerInterface extends BlockInterface, ContainerInterface {
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
	function &add_block( array $block_config ): BlockInterface;
}
