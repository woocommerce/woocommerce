<?php
/**
 * Top rated products block.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * ProductTopRated class.
 */
class ProductTopRated extends AbstractProductGrid {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-top-rated';

	/**
	 * Force orderby to rating.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_block_query_args( &$query_args ) {
		$query_args['orderby'] = 'rating';
	}
}
