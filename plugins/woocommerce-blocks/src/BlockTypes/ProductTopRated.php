<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	 * Show only products with ratings and order by rating.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_block_query_args( &$query_args ) {
		$query_args['orderby'] = 'rating';

		$this->meta_query[] = array(
			'key'     => '_wc_average_rating',
			'value'   => 0,
			'compare' => '>',
		);
	}
}
