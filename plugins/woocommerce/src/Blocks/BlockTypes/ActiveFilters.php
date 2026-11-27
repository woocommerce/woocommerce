<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ActiveFilters class.
 */
class ActiveFilters extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'active-filters';
}
