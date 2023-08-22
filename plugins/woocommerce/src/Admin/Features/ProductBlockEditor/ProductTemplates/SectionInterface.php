<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

/**
 * Interface for block containers.
 */
interface SectionInterface extends ContainerInterface {

	/**
	 * Adds a new section block.
	 *
	 * @param array $block_config block config.
	 * @return BlockInterface new block section.
	 */
	public function add_section( array $block_config ): SectionInterface;
}
