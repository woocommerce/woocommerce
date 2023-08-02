<?php

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Interface for block containers.
 */
interface BlockContainerInterface {

	/**
	 * Add a block to the block container.
	 *
	 * @param array $block_config The block data.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If a block with the specified ID already exists in the template.
	 */
	public function &add_block( array $block_config ): BlockInterface;

	/**
	 * Get the parent block container that the block container belongs to.
	 */
	public function &get_parent(): ?BlockContainerInterface;

	/**
	 * Get the root template that the block container belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface;

	/**
	 * Get the inner blocks as a formatted template.
	 */
	public function get_inner_blocks_formatted_template(): array;
}
