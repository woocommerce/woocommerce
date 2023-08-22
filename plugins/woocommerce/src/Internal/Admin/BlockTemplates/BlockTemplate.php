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
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'woocommerce-block-template';
	}

	/**
	 * Generate a block ID based on a base.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_block( array $block_config ): BlockInterface {
		$block = new Block( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}
}
