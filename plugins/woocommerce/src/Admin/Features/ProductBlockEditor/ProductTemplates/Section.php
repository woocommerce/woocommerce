<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlock;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainerTrait;

class Section extends AbstractBlock implements ContainerInterface {
	use BlockContainerTrait;

	function __construct( array $config, &$root_template, ContainerInterface &$parent = null ) {
		parent::__construct( array_merge( array(  'blockName' => 'woocommerce/product-section' ), $config ), $root_template, $parent );
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

	/**
	 * Generate a block ID based on a base.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_columns( array ...$column_block_configs ): BlockInterface {
		$columns_block = $this->add_block( [
			'blockName' => 'core/columns',
		] );
		foreach ( $column_block_configs as $column_block_config ) {
			$column = $columns_block->add_block( [
				'blockName'  => 'core/column',
				'attributes' => [
					'templateLock' => 'all',
				]
			] );
			$column->add_block( $column_block_config );
		}
		return $columns_block;
	}
}
