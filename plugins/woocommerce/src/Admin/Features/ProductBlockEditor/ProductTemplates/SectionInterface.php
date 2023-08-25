<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;


/**
 * Interface for block containers.
 */
interface SectionInterface extends BlockContainerInterface {

	/**
	 * Adds a new section block.
	 *
	 * @param array $block_config block config.
	 * @return SectionInterface new block section.
	 */
	public function add_section( array $block_config ): SectionInterface;

	/**
	 * Adds a new block to the section block.
	 *
	 * @param array $block_config block config.
	 */
	public function add_block( array $block_config ): BlockInterface;
}
