<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;


/**
 * Interface for section containers, which contain sub-sections and blocks.
 */
interface SectionInterface extends BlockContainerInterface {

	/**
	 * Adds a new sub-section to the section.
	 *
	 * @param array $block_config block config.
	 * @return SubsectionInterface new block sub-section.
	 */
	public function add_subsection( array $block_config ): SubsectionInterface;

	/**
	 * Adds a new block to the section.
	 *
	 * @param array $block_config block config.
	 */
	public function add_block( array $block_config ): BlockInterface;

	/**
	 * Adds a new sub-section to the section.
	 *
	 * @deprecated 8.6.0
	 *
	 * @param array $block_config The block data.
	 */
	public function add_section( array $block_config ): SubsectionInterface;
}
