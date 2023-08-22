<?php
/**
 * WooCommerce Product Block class.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlock;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainerTrait;

/**
 * Class for Product block.
 */
class ProductBlock extends AbstractBlock implements ContainerInterface {
	use BlockContainerTrait;
	/**
	 * Adds block to the section block.
	 *
	 * @param array $block_config The block data.
	 */
	public function &add_block( array $block_config ): BlockInterface {
		$block = new ProductBlock( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}
}
