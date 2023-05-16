<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * PriceFilter class.
 */
class PriceFilter extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name     = 'price-filter';
	const MIN_PRICE_QUERY_VAR = 'min_price';
	const MAX_PRICE_QUERY_VAR = 'max_price';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
