<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

class Section extends ProductBlock implements ContainerInterface {

	function __construct( array $config, &$root_template, ContainerInterface &$parent = null ) {
		parent::__construct( array_merge( array(  'blockName' => 'woocommerce/product-section' ), $config ), $root_template, $parent );
	}

	/**
	 * Add a section block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_section( array $block_config ): BlockInterface {
		$block = new Section( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}

	/**
	 * Adds columns to a template.
	 *
	 * @param array $column_block_configs Column block data.
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
