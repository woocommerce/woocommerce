<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlockTemplate;

/**
 * Custom block template class.
 */
class CustomBlockTemplate extends AbstractBlockTemplate {
	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_custom_block( array $block_config ): BlockInterface {
		$block = new CustomBlock( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}
}
