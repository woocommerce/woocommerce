<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Generic block with container properties to be used in BlockTemplate.
 */
class Block extends AbstractBlock implements BlockContainerInterface {
	use BlockContainerTrait;

	/**
	 * Generate a block ID based on a base.
	 *
	 * @param string $id_base The base to use when generating an ID.
	 * @return string
	 */
	public function &add_block( array $block_config ): BlockInterface {
		return $this->add_inner_block( $block_config );
	}
}
