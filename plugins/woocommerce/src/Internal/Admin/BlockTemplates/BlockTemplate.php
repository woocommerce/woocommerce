<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Block template class.
 */
class BlockTemplate extends AbstractBlockTemplate {
	/**
	 * Generate a block ID based on a base.
	 *
	 * @param string $id_base The base to use when generating an ID.
	 * @return string
	 */
	public function add_block( array $block_config, $block_class = 'Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block' ): BlockInterface {
		return $this->add_inner_block( $block_config, $block_class );
	}
}
