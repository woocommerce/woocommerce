<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * The ProductFiltersWrapper block class.
 */
class ProductFiltersWrapper extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filters-wrapper';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
