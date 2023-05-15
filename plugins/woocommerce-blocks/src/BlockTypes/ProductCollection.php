<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductCollection class.
 */
class ProductCollection extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-collection';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
