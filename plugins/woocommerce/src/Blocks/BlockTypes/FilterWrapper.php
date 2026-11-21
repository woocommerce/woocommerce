<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * FilterWrapper class.
 */
class FilterWrapper extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'filter-wrapper';
}
