<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlock;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainerTrait;

class Group extends AbstractBlock implements ContainerInterface {
	use BlockContainerTrait;

	function __construct( array $config, $root_template, ContainerInterface &$parent = null ) {
		parent::__construct( array_merge( array(  'blockName' => 'woocommerce/product-tab' ), $config ), $root_template, $parent );
	}

	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_section( array $block_config ): BlockInterface {
		$block = new Section( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
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
