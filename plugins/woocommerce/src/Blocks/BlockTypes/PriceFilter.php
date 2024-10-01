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
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'attributes', array_values( wc_get_attribute_taxonomies() ) );

		// Enqueue any `queryState` that the UI will need to be aware of
		// (Ex: the category id if we're on a category page, the tag id if we're on a tag page/etc).
		$query_state = [];

		if ( is_product_category() ) {
			$query_state['category'] = get_queried_object_id();
		}
		if ( is_product_tag() ) {
			$query_state['tag'] = get_queried_object()->term_id;
		}

		$this->asset_data_registry->add( 'queryState', $query_state );
	}
}
