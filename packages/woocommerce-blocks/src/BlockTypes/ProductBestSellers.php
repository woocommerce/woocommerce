<?php
/**
 * Product best sellers block.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * ProductBestSellers class.
 */
class ProductBestSellers extends AbstractProductGrid {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-best-sellers';

	/**
	 * Set args specific to this block
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_block_query_args( &$query_args ) {
		$query_args['orderby'] = 'popularity';
	}
}
