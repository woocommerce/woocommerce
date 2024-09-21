<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * PriceFilter class.
 */
abstract class AbstractFilterBlock extends AbstractBlock {
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		// Enqueue any `queryState` that the UI will need to be aware of
		// (Ex: the category id if we're on a category page, the tag id if we're on a tag page/etc)
		$queryState = [];

		if (is_product_category()) {
			$queryState['category'] = get_queried_object_id();
		}
		if (is_product_tag()) {
			$queryState['tag'] = get_queried_object()->term_id;
		}

		$this->asset_data_registry->add( 'queryState', $queryState );
	}
}
