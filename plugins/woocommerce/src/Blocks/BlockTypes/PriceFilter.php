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

	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		if (is_product_category()) {
			$this->asset_data_registry->add( 'categoryId', get_queried_object_id() );
		}
	}
}
