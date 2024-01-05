<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * FilledCartBlock class.
 */
class FilterWrapper extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'filter-wrapper';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
